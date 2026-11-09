{{-- Runtime único de gráficos (docs/198). @once evita carregar e executar duas cópias na mesma página. --}}
@once
@push('scripts')<script src="@ativo('js/libs/echarts-g360.min.js')"></script>@endpush
@endonce
