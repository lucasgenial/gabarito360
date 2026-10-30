{{-- Ícones do menu (traço, herdam a cor do texto). Uso: @include('layouts._icone', ['nome' => 'casa']) --}}
<svg class="nav-icone" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($nome)
  @case('casa')<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>@break
  @case('prova')<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>@break
  @case('camera')<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>@break
  @case('turma')<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c2.9.4 5 2.8 5 5.8"/>@break
  @case('aluno')<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>@break
  @case('escola')<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M10 21v-5h4v5"/>@break
  @case('equipe')<circle cx="8" cy="9" r="3"/><circle cx="16" cy="9" r="3"/><path d="M2 20c0-3 2.7-5 6-5M22 20c0-3-2.7-5-6-5M8 15c1.2-.6 2.5-1 4-1s2.8.4 4 1"/>@break
  @case('grafico')<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>@break
  @case('imprimir')<path d="M7 8V4h10v4"/><rect x="4" y="8" width="16" height="8" rx="2"/><path d="M7 14h10v6H7z"/>@break
  @case('ajustes')<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>@break
  @case('lixeira')<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>@break
  @case('cadeado')<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>@break
  @case('compartilhar')<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.3 11l7.4-4M8.3 13l7.4 4"/>@break
  @default<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>
@endswitch
</svg>
