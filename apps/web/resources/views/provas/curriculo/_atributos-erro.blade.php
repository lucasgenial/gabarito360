@if($mensagens->has($campo) || $errors->has($campo)) aria-invalid="true" aria-describedby="erro-{{ $campo }}" @endif
