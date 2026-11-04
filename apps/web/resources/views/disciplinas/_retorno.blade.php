@foreach($retorno ?? [] as $campo=>$valor)<input type="hidden" name="{{ $campo }}" value="{{ $valor }}">@endforeach
