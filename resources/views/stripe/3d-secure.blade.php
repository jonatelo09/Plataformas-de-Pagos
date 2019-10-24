@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Completar los pasos de seguridad!</div>

                <div class="card-body">
                  Debe seguir algunos pasos con tu banco para completar este pago. Vamos a hacerlo!.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
    const stripe = Stripe('{{ config('services.stripe.key')}} ');

    stripe.handleCardAction("{{ $clientSecret }} ")
        .then(function (result){
            if (result.error) {
                window.location.replace("{{ route('cancelado')}} ");
            }else{
                window.location.replace("{{ route('aprobada')}} ");
            }
        });
</script>
@endpush
@endsection