<?php

namespace App\Http\Controllers;

use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\Request;

class PaymentController extends Controller {

	protected $paymentPlatformResolver;

	public function __construct(PaymentPlatformResolver $paymentPlatformResolver) {
		$this->middleware('auth');
		$this->paymentPlatformResolver = $paymentPlatformResolver;
	}

	public function pay(Request $request) {
		$rules = [
			'value' => ['required', 'numeric', 'min:5'],
			'currency' => ['required', 'exists:currencies,iso'],
			'payment_platform' => ['required', 'exists:payment_platforms,id'],
		];

		//dd($request->all());
		$request->validate($rules);

		$paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

		//$paymentPlatform = resolve(PayPalService::class);
		//
		session()->put('paymentPlatformId', $request->payment_platform);

		return $paymentPlatform->handlePayment($request);
	}

	public function aprobada() {

		if (session()->has('paymentPlatformId')) {
			$paymentPlatform = $this->paymentPlatformResolver->resolveService(session()->get('paymentPlatformId'));

			return $paymentPlatform->handleAprobada();
		}
		//$paymentPlatform = resolve(PayPalService::class);

		$notificacion = "We cannot retrieve your payment platform. Try again, please.";

		return redirect()
			->route('home')
			->witherrors($notificacion);
	}

	public function cancelado() {

		$notificacion = "Tu pago ha sido cancelado con exito";
		return redirect()
			->route('home')
			->witherrors($notificacion);
	}
}
