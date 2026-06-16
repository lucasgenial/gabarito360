@extends('layouts.app')

@section('title', 'Escolas')

@php
    use App\Enums\StatusEnum;

    $modalMode = old('form_mode', 'create');
    $modalOpen = $errors->any() && in_array($modalMode, ['create', 'edit'], true);
    $initialFormState = [
        'open' => $modalOpen,
        'mode' => $modalMode,
        'schoolId' => old('school_id'),
        'values' => [
            'nucleo_id' => old('nucleo_id'),
            'nome' => old('nome', ''),
            'inep' => old('inep', ''),
            'rede' => old('rede', 'estadual'),
            'endereco' => old('endereco', ''),
            'cidade' => old('cidade', ''),
            'uf' => old('uf', ''),
            'telefone' => old('telefone', ''),
            'email' => old('email', ''),
            'diretor' => old('diretor', ''),
            'ativa' => filter_var(old('ativa', '1'), FILTER_VALIDATE_BOOLEAN),
        ],
    ];
@endphp

@section('content')
    @if (session('school_success'))
        <div class="toast-stack" aria-live="polite">
            <div class="toast toast-success" data-toast>
                <div>
                    <strong>Alteracao concluida</strong>
                    <span>{{ session('school_success') }}</span>
                </div>
                <button type="button" class="toast-close" data-toast-close aria-label="Fechar mensagem">×</button>
            </div>
        </div>
    @endif

    <div class="breadcrumb">
        <a href="{{ route('portal.dashboard') }}">Inicio</a>
        <span class="sep">/</span>
        <span>Escolas</span>
    </div>

    <div class="row-between page-toolbar">
        <div>
            <h1 class="page-title">Escolas</h1>
            <p class="page-sub">Gerencie as unidades escolares autorizadas no seu contexto atual.</p>
        </div>

        <div class="page-actions">
            <form method="GET" action="{{ route('portal.schools.index') }}" class="page-search-form">
                <div class="search-box">
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                    <input
                        id="search-escolas"
                        class="input"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Buscar escola..."
                        autocomplete="off"
                    >
                </div>
            </form>

            @if ($canCreateSchool)
                <button type="button" class="btn btn-primary" data-school-open="create">
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Nova escola
                </button>
            @endif
        </div>
    </div>

    <div class="schools-kpi-strip">
        <div class="card card-pad schools-kpi-card">
            <div class="schools-kpi-value schools-kpi-value-primary">{{ number_format($kpis['total'], 0, ',', '.') }}</div>
            <div class="schools-kpi-label">Escolas cadastradas</div>
        </div>
        <div class="card card-pad schools-kpi-card">
            <div class="schools-kpi-value schools-kpi-value-success">{{ number_format($kpis['active'], 0, ',', '.') }}</div>
            <div class="schools-kpi-label">Escolas ativas</div>
        </div>
        <div class="card card-pad schools-kpi-card">
            <div class="schools-kpi-value">{{ number_format($kpis['students'], 0, ',', '.') }}</div>
            <div class="schools-kpi-label">Alunos totais</div>
        </div>
        <div class="card card-pad schools-kpi-card">
            <div class="schools-kpi-value">{{ number_format($kpis['active_classes'], 0, ',', '.') }}</div>
            <div class="schools-kpi-label">Turmas ativas</div>
        </div>
    </div>

    @if ($schools->isNotEmpty())
        <div class="escola-grid">
            @foreach ($schools as $school)
                @php
                    $isActive = $school->status === StatusEnum::ACTIVE;
                    $schoolData = [
                        'id' => $school->id,
                        'nome' => $school->nome,
                        'inep' => $school->inep,
                        'rede' => $school->rede ?: 'estadual',
                        'endereco' => $school->endereco_texto,
                        'cidade' => $school->municipio,
                        'uf' => $school->estado,
                        'telefone' => $school->telefone,
                        'email' => $school->email,
                        'diretor' => $school->diretor,
                        'ativa' => $isActive,
                    ];
                @endphp

                <article @class(['escola-card', 'is-inactive' => ! $isActive])>
                    <div class="escola-card-head">
                        <div class="escola-icon" aria-hidden="true">E</div>
                        <div class="escola-head-copy">
                            <div class="escola-name">{{ $school->nome }}</div>
                            <div class="escola-code">
                                {{ $school->inep ? 'INEP: '.$school->inep : 'INEP nao informado' }}
                            </div>
                        </div>
                        <div class="escola-status">
                            <span @class([
                                'badge',
                                'badge-success' => $isActive,
                                'badge-muted' => ! $isActive,
                            ])>
                                {{ $isActive ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>
                    </div>

                    <div class="escola-card-body">
                        <div class="escola-meta">
                            <div class="escola-meta-row">
                                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                                <span>{{ $school->endereco_texto ?: trim($school->municipio.' / '.$school->estado) }}</span>
                            </div>

                            @if ($school->telefone)
                                <div class="escola-meta-row">
                                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                                    </svg>
                                    <span>{{ $school->telefone }}</span>
                                </div>
                            @endif

                            @if ($school->email)
                                <div class="escola-meta-row">
                                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                        <polyline points="22,6 12,13 2,6" />
                                    </svg>
                                    <span>{{ $school->email }}</span>
                                </div>
                            @endif

                            <div class="escola-meta-row">
                                <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <span>{{ $school->diretor ? 'Direcao: '.$school->diretor : 'Direcao nao informada' }}</span>
                            </div>

                            @unless ($isActive)
                                <div class="escola-meta-row escola-meta-row-danger">
                                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="12" y1="8" x2="12" y2="12" />
                                        <line x1="12" y1="16" x2="12.01" y2="16" />
                                    </svg>
                                    <span>Escola inativa no momento.</span>
                                </div>
                            @endunless
                        </div>
                    </div>

                    <div class="escola-card-foot">
                        <div class="escola-stats">
                            <div class="escola-stat">
                                <div class="val">{{ number_format($school->alunos_count, 0, ',', '.') }}</div>
                                <div class="lbl">Alunos</div>
                            </div>
                            <div class="escola-stat">
                                <div class="val">{{ number_format($school->turmas_count, 0, ',', '.') }}</div>
                                <div class="lbl">Turmas</div>
                            </div>
                            <div class="escola-stat">
                                <div class="val">{{ number_format($school->provas_count, 0, ',', '.') }}</div>
                                <div class="lbl">Provas</div>
                            </div>
                        </div>

                        <div class="escola-actions">
                            @can('update', $school)
                                <button type="button" class="btn btn-sm btn-secondary" data-school-edit='@json($schoolData)'>
                                    Editar
                                </button>
                            @endcan

                            @can('update', $school)
                                @unless ($isActive)
                                    <form method="POST" action="{{ route('portal.schools.reactivate', $school) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Reativar</button>
                                    </form>
                                @endunless
                            @endcan

                            <a
                                href="{{ route('portal.schools.show', $school) }}"
                                @class([
                                    'btn',
                                    'btn-sm',
                                    'btn-primary' => $isActive,
                                    'btn-secondary' => ! $isActive,
                                ])
                            >
                                Ver mais
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pagination-bar">
            <div class="pagination-meta">
                Mostrando {{ $schools->firstItem() }}-{{ $schools->lastItem() }} de {{ $schools->total() }} escolas
            </div>
            <div class="pagination-actions">
                @if ($schools->onFirstPage())
                    <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Anterior</span>
                @else
                    <a class="btn btn-sm btn-secondary" href="{{ $schools->previousPageUrl() }}">Anterior</a>
                @endif

                <span class="pagination-current">Pagina {{ $schools->currentPage() }} de {{ $schools->lastPage() }}</span>

                @if ($schools->hasMorePages())
                    <a class="btn btn-sm btn-secondary" href="{{ $schools->nextPageUrl() }}">Proxima</a>
                @else
                    <span class="btn btn-sm btn-ghost is-disabled" aria-disabled="true">Proxima</span>
                @endif
            </div>
        </div>
    @else
        <div class="card card-pad empty-state">
            <div class="empty-icon" aria-hidden="true">E</div>
            <h3>{{ $search !== '' ? 'Nenhuma escola encontrada' : 'Nenhuma escola cadastrada' }}</h3>
            <p>
                {{ $search !== '' ? 'Tente ajustar o termo de busca para encontrar outra unidade.' : 'Assim que uma escola estiver disponivel no seu escopo, ela aparecera aqui.' }}
            </p>

            @if ($search !== '')
                <a href="{{ route('portal.schools.index') }}" class="btn btn-secondary">Limpar busca</a>
            @elseif ($canCreateSchool)
                <button type="button" class="btn btn-primary" data-school-open="create">Cadastrar primeira escola</button>
            @endif
        </div>
    @endif

    <div
        id="modal-escola"
        @class(['modal-backdrop', 'open' => $modalOpen])
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
        aria-hidden="{{ $modalOpen ? 'false' : 'true' }}"
    >
        <div class="modal">
            <div class="modal-head">
                <h2 id="modal-title">{{ $modalMode === 'edit' ? 'Editar escola' : 'Nova escola' }}</h2>
                <button type="button" class="modal-close" data-school-close aria-label="Fechar">×</button>
            </div>

            <form
                id="school-form"
                method="POST"
                action="{{ route('portal.schools.store') }}"
                data-store-url="{{ route('portal.schools.store') }}"
                data-update-url="{{ route('portal.schools.update', '__SCHOOL__') }}"
            >
                @csrf
                <input type="hidden" name="form_mode" id="school-form-mode" value="{{ $modalMode }}">
                <input type="hidden" name="school_id" id="school-id" value="{{ old('school_id') }}">

                <div class="modal-body">
                    @if ($modalOpen)
                        <div class="form-error-summary" role="alert">
                            Revise os campos destacados antes de salvar a escola.
                        </div>
                    @endif

                    @if ($manageableNuclei->count() > 1)
                        <div class="form-section" data-school-create-only>
                            <div class="form-section-label">Escopo</div>
                            <div class="field">
                                <label for="m-nucleo">Nucleo responsavel <span class="field-required">*</span></label>
                                <select id="m-nucleo" name="nucleo_id" class="select" @disabled(! $canCreateSchool)>
                                    <option value="">Selecione um nucleo</option>
                                    @foreach ($manageableNuclei as $nucleo)
                                        <option value="{{ $nucleo->id }}" @selected(old('nucleo_id') === $nucleo->id)>
                                            {{ $nucleo->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="field-help">Obrigatorio quando seu usuario gerencia mais de um nucleo.</div>
                                @error('nucleo_id')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <div class="form-section">
                        <div class="form-section-label">Identificacao</div>
                        <div class="field">
                            <label for="m-nome">Nome da escola <span class="field-required">*</span></label>
                            <input id="m-nome" name="nome" type="text" class="input" value="{{ old('nome') }}" required>
                            @error('nome')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row-2">
                            <div class="field">
                                <label for="m-inep">Codigo INEP</label>
                                <input id="m-inep" name="inep" type="text" class="input" value="{{ old('inep') }}" maxlength="20">
                                @error('inep')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="m-rede">Tipo de rede</label>
                                <select id="m-rede" name="rede" class="select">
                                    @foreach (['estadual' => 'Estadual', 'municipal' => 'Municipal', 'federal' => 'Federal', 'privada' => 'Privada'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('rede', 'estadual') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('rede')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-label">Endereco</div>
                        <div class="field">
                            <label for="m-endereco">Logradouro</label>
                            <input id="m-endereco" name="endereco" type="text" class="input" value="{{ old('endereco') }}">
                            @error('endereco')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row-2">
                            <div class="field">
                                <label for="m-cidade">Cidade <span class="field-required">*</span></label>
                                <input id="m-cidade" name="cidade" type="text" class="input" value="{{ old('cidade') }}" required>
                                @error('cidade')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="m-uf">UF <span class="field-required">*</span></label>
                                <input id="m-uf" name="uf" type="text" class="input" value="{{ old('uf') }}" maxlength="2" required>
                                @error('uf')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-label">Contato</div>
                        <div class="form-row-2">
                            <div class="field">
                                <label for="m-telefone">Telefone</label>
                                <input id="m-telefone" name="telefone" type="tel" class="input" value="{{ old('telefone') }}">
                                @error('telefone')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="field">
                                <label for="m-email">E-mail institucional</label>
                                <input id="m-email" name="email" type="email" class="input" value="{{ old('email') }}">
                                @error('email')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="field">
                            <label for="m-diretor">Direcao</label>
                            <input id="m-diretor" name="diretor" type="text" class="input" value="{{ old('diretor') }}">
                            @error('diretor')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-section form-section-last">
                        <div class="form-section-label">Status</div>
                        <label class="checkbox-row">
                            <input id="m-ativa" name="ativa" type="checkbox" value="1" @checked(filter_var(old('ativa', '1'), FILTER_VALIDATE_BOOLEAN))>
                            Escola ativa (aparece na selecao de provas e turmas)
                        </label>
                        @error('ativa')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" data-school-close>Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar escola</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modal-escola');
            const modalTitle = document.getElementById('modal-title');
            const schoolForm = document.getElementById('school-form');
            const formModeInput = document.getElementById('school-form-mode');
            const schoolIdInput = document.getElementById('school-id');
            const searchInput = document.getElementById('search-escolas');
            const searchForm = searchInput ? searchInput.form : null;
            const initialState = @json($initialFormState);

            if (!modal || !schoolForm || !formModeInput || !schoolIdInput) {
                return;
            }

            const fieldMap = {
                nucleo_id: document.getElementById('m-nucleo'),
                nome: document.getElementById('m-nome'),
                inep: document.getElementById('m-inep'),
                rede: document.getElementById('m-rede'),
                endereco: document.getElementById('m-endereco'),
                cidade: document.getElementById('m-cidade'),
                uf: document.getElementById('m-uf'),
                telefone: document.getElementById('m-telefone'),
                email: document.getElementById('m-email'),
                diretor: document.getElementById('m-diretor'),
                ativa: document.getElementById('m-ativa'),
            };
            const createOnlyBlocks = Array.from(document.querySelectorAll('[data-school-create-only]'));
            const storeUrl = schoolForm.dataset.storeUrl;
            const updateUrlTemplate = schoolForm.dataset.updateUrl;

            function ensureMethodField(mode) {
                let methodField = schoolForm.querySelector('input[name="_method"]');

                if (mode === 'edit') {
                    if (!methodField) {
                        methodField = document.createElement('input');
                        methodField.type = 'hidden';
                        methodField.name = '_method';
                        schoolForm.insertBefore(methodField, schoolForm.firstChild);
                    }

                    methodField.value = 'PATCH';
                    return;
                }

                if (methodField) {
                    methodField.remove();
                }
            }

            function fillForm(values) {
                Object.entries(fieldMap).forEach(function ([key, field]) {
                    if (!field) {
                        return;
                    }

                    if (field.type === 'checkbox') {
                        field.checked = Boolean(values[key]);
                        return;
                    }

                    field.value = values[key] ?? '';
                });
            }

            function resetCreateOnlyVisibility(mode) {
                createOnlyBlocks.forEach(function (block) {
                    block.hidden = mode === 'edit';
                });
            }

            function setMode(mode, school) {
                const isEdit = mode === 'edit';
                const values = school || {
                    nome: '',
                    inep: '',
                    rede: 'estadual',
                    endereco: '',
                    cidade: '',
                    uf: '',
                    telefone: '',
                    email: '',
                    diretor: '',
                    ativa: true,
                    nucleo_id: '',
                };

                formModeInput.value = mode;
                schoolIdInput.value = isEdit ? school.id : '';
                modalTitle.textContent = isEdit ? 'Editar escola' : 'Nova escola';
                schoolForm.action = isEdit ? updateUrlTemplate.replace('__SCHOOL__', school.id) : storeUrl;

                ensureMethodField(mode);
                resetCreateOnlyVisibility(mode);
                fillForm(values);
            }

            function openModal() {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
                window.setTimeout(function () {
                    if (fieldMap.nome) {
                        fieldMap.nome.focus();
                    }
                }, 60);
            }

            function closeModal() {
                modal.classList.remove('open');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('[data-school-open="create"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    setMode('create');
                    openModal();
                });
            });

            document.querySelectorAll('[data-school-edit]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const school = JSON.parse(button.getAttribute('data-school-edit') || '{}');
                    setMode('edit', school);
                    openModal();
                });
            });

            document.querySelectorAll('[data-school-close]').forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('open')) {
                    closeModal();
                }
            });

            if (initialState.open) {
                setMode(initialState.mode, initialState.mode === 'edit'
                    ? { id: initialState.schoolId, ...initialState.values }
                    : initialState.values);
                openModal();
            } else {
                setMode('create');
            }

            if (searchInput && searchForm) {
                let debounceId = null;

                searchInput.addEventListener('input', function () {
                    window.clearTimeout(debounceId);
                    debounceId = window.setTimeout(function () {
                        searchForm.requestSubmit();
                    }, 320);
                });
            }
        });
    </script>
@endpush
