<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

Class PayPalService {

	use ConsumesExternalServices;

	protected $baseUri;

	protected $clientId;

	protected $clientSecret;

	public function __construct() {
		$this->baseUri = config('services.paypal.base_uri');
		$this->clientId = config('services.paypal.client_id');
		$this->clientSecret = config('services.paypal.client_secret');
	}

	public function resolveAuthorization(&$queryParams, &$formParams, &$headers) {
		$headers['Authorization'] = $this->resolveAccessToken(); //Aqui se genera el token de accesos que se anexa a la cabecera de autorizacion

	}

	public function decodeResponse($response) {

		return json_decode($response);
	}

	public function resolveAccessToken() {

		$credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");

		return "Basic {$credentials}";
	}

	public function handlePayment(Request $request) {

		$order = $this->createOrder($request->value, $request->currency);

		$orderLinks = collect($order->links);

		$approve = $orderLinks->where('rel', 'approve')->first();

		session()->put('aprobadaId', $order->id);

		return redirect($approve->href);
	}

	public function handleAprobada() {
		if (session()->has('aprobadaId')) {
			$aprobadaId = session()->get('aprobadaId');

			$payment = $this->capturePayment($aprobadaId);

			$name = $payment->payer->name->given_name;
			$payment = $payment->purchase_units[0]->payments->captures[0]->amount;
			$amount = $payment->value;
			$currency = $payment->currency_code;

			return redirect()
				->route('home')
				->withSuccess(['payment' => "Gracias, {$name}. Hemos recivido tu pago de {$amount} ({$currency} )"]);
		}

		return redirect()
			->route('home')
			->withErrors("No podemos capturar el pago. Intente de nuevo, Por Favor");
	}

	public function createOrder($value, $currency) {
		return $this->makeRequest(
			'POST',
			'/v2/checkout/orders',
			[],
			[
				'intent' => 'CAPTURE',
				'purchase_units' => [
					0 => [
						'amount' => [
							'currency_code' => strtoupper($currency),
							'value' => round($value * $factor = $this->resolverFactor($currency)) / $factor,
						],
					],
				],
				'application_context' => [
					'brand_name' => config('app.name'),
					'shipping_preference' => 'NO_SHIPPING',
					'user_action' => 'PAY_NOW',
					'return_url' => route('aprobada'),
					'cancel_url' => route('cancelado'),
				],
			],
			[],

			$isJsonRequest = true
		);
	}

	public function capturePayment($aprobadaId) {
		return $this->makeRequest(
			'POST',
			"/v2/checkout/orders/{$aprobadaId}/capture",
			[],
			[],
			[
				'Content-Type' => 'application/json',
			]
		);
	}

	public function resolverFactor($currency) {

		$zeroDecimalCurrencies = ['JPY'];

		if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
			return 1;
		}

		return 100;

	}
}