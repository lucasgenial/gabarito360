@if (session('success'))
    <div class="alert alert-success" role="status">
        <strong>Concluido.</strong>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>Revise os dados informados.</strong>
        <span>Ha campos que precisam de correcao antes de continuar.</span>
    </div>
@endif
