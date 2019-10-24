<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

Class StripeService {

	use ConsumesExternalServices;

	protected $key;

	protected $secret;

	protected $baseUri;

	public function __construct() {
		$this->baseUri = config('services.stripe.base_uri');
		$this->key = config('services.stripe.key');
		$this->secret = config('services.stripe.secret');
	}

	public function resolveAuthorization(&$queryParams, &$formParams, &$headers) {
		$headers['Authorization'] = $this->resolveAccessToken();

	}

	public function decodeResponse($response) {

		return json_decode($response);
	}

	public function resolveAccessToken() {

		return "Bearer {$this->secret}";
	}

	public function handlePayment(Request $request) {
		$request->validate([
			'payment_method' => 'required',
		]);

		$intent = $this->createIntent($request->value, $request->currency, $request->payment_method);

		session()->put('paymentIntentId', $intent->id);

		return redirect()->route('aprobada');
	}

	public function handleAprobada() {

		if (session()->has('paymentIntentId')) {

			$paymentIntentId = session()->get('paymentIntentId');

			$confirmation = $this->confirmPayment($paymentIntentId);

			//dd($confirmation);

			if ($confirmation->status === 'requires_action') {

				$clientSecret = $confirmation->client_secret;

				return view('stripe.3d-secure')->with([
					'clientSecret' => $clientSecret,

				]);
			}
			if ($confirmation->status === 'succeeded') {
				$name = $confirmation->charges->data[0]->billing_details->name;
				$currency = strtoupper($confirmation->currency);
				$amount = $confirmation->amount / $this->resolverFactor($currency);

				return redirect()
					->route('home')
					->withSuccess(['payment' => "Gracias, {$name}. Hemos recivido tu pago de {$amount} ({$currency} )"]);
			}
		}

		return redirect()
			->route('home')
			->withErrors('No pudimos confirmar su pago. por favor, inténtalo de nuevo.');
	}

	public function createIntent($value, $currency, $paymentMethod) {

		return $this->makeRequest(
			'POST',
			'/v1/payment_intents',
			[],
			[
				'amount' => round($value * $this->resolverFactor($currency)), //
				'currency' => strtolower($currency),
				'payment_method' => $paymentMethod,
				'confirmation_method' => 'manual',
			]
		);
	}

	public function confirmPayment($paymentIntentId) {
		return $this->makeRequest(
			'POST',
			"v1/payment_intents/{$paymentIntentId}/confirm"
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