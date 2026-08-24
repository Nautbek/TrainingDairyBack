@php use Modules\Nutrition\Enums\ProductStatus; @endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Блюда — админка</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #f8f8f7;
            color: #1b1b18;
            line-height: 1.5;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        .page-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav-link {
            font-size: 0.875rem;
            color: #706f6c;
            text-decoration: none;
        }

        .nav-link:hover { color: #1b1b18; }

        .search-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e5e3;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-family: inherit;
            background: #fff;
        }

        .search-input:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .search-btn,
        .search-reset {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #e5e5e3;
            background: #fff;
            color: #1b1b18;
        }

        .search-btn {
            background: #1b1b18;
            color: #fff;
            border-color: #1b1b18;
        }

        .search-btn:hover { opacity: 0.9; }

        .search-reset:hover { background: #f5f5f3; }

        .list-meta {
            font-size: 0.8125rem;
            color: #706f6c;
            margin-bottom: 1rem;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .tab {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid transparent;
            transition: opacity 0.15s;
        }

        .tab:hover { opacity: 0.85; }

        .tab-draft {
            background: #fef9c3;
            color: #854d0e;
            border-color: #fde047;
        }

        .tab-draft.active {
            background: #facc15;
            color: #422006;
            border-color: #ca8a04;
        }

        .tab-active {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
        }

        .tab-active.active {
            background: #22c55e;
            color: #fff;
            border-color: #16a34a;
        }

        .tab-decline {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fca5a5;
        }

        .tab-decline.active {
            background: #ef4444;
            color: #fff;
            border-color: #dc2626;
        }

        .tab:not(.active) { opacity: 0.75; }

        .dish-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .dish-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            background: #fff;
            border: 1px solid #e5e5e3;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .dish-item.saved {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .dish-info { flex: 1; min-width: 0; }

        .field-input,
        .field-select {
            width: 100%;
            padding: 0.375rem 0.5rem;
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            font-size: inherit;
            font-family: inherit;
            background: #fff;
            color: #1b1b18;
        }

        .field-input:focus,
        .field-select:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .field-input--name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.375rem;
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            align-items: center;
            margin: 0.375rem 0;
        }

        .meta-field {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.6875rem;
            color: #a8a7a4;
        }

        .meta-field .field-input,
        .meta-field .field-select {
            font-size: 0.6875rem;
            padding: 0.2rem 0.375rem;
            width: auto;
            min-width: 6rem;
        }

        .meta-field .field-input--water { min-width: 5rem; }
        .meta-field .field-input--uuid { min-width: 14rem; font-family: ui-monospace, monospace; }

        .computed-totals {
            font-size: 0.75rem;
            color: #706f6c;
            margin: 0.375rem 0;
        }

        .ingredients-title {
            font-size: 0.6875rem;
            color: #a8a7a4;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin: 0.5rem 0 0.25rem;
        }

        .ingredient-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            align-items: center;
            margin-bottom: 0.375rem;
        }

        .ingredient-row .field-input--ing-name { flex: 1; min-width: 8rem; font-size: 0.8125rem; }
        .ingredient-row .field-input--ing-macro { width: 4.5rem; font-size: 0.75rem; }

        .ingredient-remove-btn {
            border: none;
            background: transparent;
            color: #dc2626;
            cursor: pointer;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }

        .ingredient-add-btn {
            font-size: 0.75rem;
            color: #2563eb;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.25rem 0;
        }

        .flash {
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .flash-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .dish-actions {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-shrink: 0;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border: none;
            background: transparent;
            color: #a8a7a4;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: color 0.15s, background 0.15s;
        }

        .action-btn:hover { background: #f5f5f3; }

        .approve-btn:hover { color: #16a34a; background: #dcfce7; }
        .decline-btn:hover { color: #dc2626; background: #fee2e2; }
        .delete-btn:hover { color: #ef4444; background: #fef2f2; }
        .save-btn:hover { color: #2563eb; background: #dbeafe; }

        .empty {
            text-align: center;
            padding: 3rem 1rem;
            color: #706f6c;
            font-size: 0.9375rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.375rem 0.75rem;
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            text-decoration: none;
            color: #1b1b18;
            font-size: 0.875rem;
            background: #fff;
        }

        .pagination a:hover { background: #f5f5f3; }

        .pagination .active span {
            background: #1b1b18;
            color: #fff;
            border-color: #1b1b18;
        }

        .pagination .disabled span {
            opacity: 0.4;
            cursor: default;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>Блюда</h1>
            <a class="nav-link" href="{{ route('admin.products.index', [], false) }}">← Продукты</a>
        </div>

        @if (session('saved_dish_id'))
            <div class="flash flash-success">Блюдо #{{ session('saved_dish_id') }} сохранено</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="search-form" method="GET" action="{{ route('admin.dishes.index', [], false) }}">
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            <input
                class="search-input"
                type="search"
                name="name"
                value="{{ $search }}"
                placeholder="Поиск по названию..."
            >
            <button class="search-btn" type="submit">Найти</button>
            @if ($search !== '')
                <a
                    class="search-reset"
                    href="{{ route('admin.dishes.index', ['status' => $currentStatus], false) }}"
                >Сброс</a>
            @endif
        </form>

        <nav class="tabs">
            @php
                $tabs = [
                    0 => ['label' => 'Черновики', 'class' => 'tab-draft'],
                    1 => ['label' => 'Активные', 'class' => 'tab-active'],
                    2 => ['label' => 'Отклонённые', 'class' => 'tab-decline'],
                ];
            @endphp

            @foreach ($tabs as $value => $tab)
                <a
                    href="{{ route('admin.dishes.index', array_filter(['status' => $value, 'name' => $search ?: null]), false) }}"
                    class="tab {{ $tab['class'] }} {{ $currentStatus === $value ? 'active' : '' }}"
                >
                    {{ $tab['label'] }}
                    @if ($counts->has($value))
                        ({{ $counts[$value] }})
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($dishes->total() > 0)
            <div class="list-meta">
                {{ $dishes->firstItem() }}–{{ $dishes->lastItem() }} из {{ $dishes->total() }}
                @if ($search !== '')
                    · поиск: «{{ $search }}»
                @endif
            </div>

            <div class="dish-list">
                @foreach ($dishes as $dish)
                    <div @class(['dish-item', 'saved' => session('saved_dish_id') === $dish->id])>
                        <form
                            id="dish-form-{{ $dish->id }}"
                            class="dish-info"
                            method="POST"
                            action="{{ route('admin.dishes.update', $dish, false) }}"
                        >
                            @csrf
                            <input type="hidden" name="_dish_id" value="{{ $dish->id }}">

                            <input
                                class="field-input field-input--name"
                                type="text"
                                name="name"
                                value="{{ $dish->name }}"
                                required
                            >

                            <div class="meta-row">
                                <span class="meta-field">#{{ $dish->id }}</span>
                                <label class="meta-field">
                                    вода, г
                                    <input class="field-input field-input--water" type="number" name="water_grams" value="{{ $dish->water_grams }}" min="0" step="0.01" required>
                                </label>
                                <label class="meta-field">
                                    автор
                                    <input class="field-input field-input--uuid" type="text" name="author_uuid" value="{{ $dish->author_uuid }}" required>
                                </label>
                                <label class="meta-field">
                                    статус
                                    <select class="field-select" name="status">
                                        @foreach ([ProductStatus::Draft, ProductStatus::Active, ProductStatus::Decline] as $statusOption)
                                            <option value="{{ $statusOption->value }}" @selected($dish->status === $statusOption)>
                                                {{ match ($statusOption) {
                                                    ProductStatus::Draft => 'Черновик',
                                                    ProductStatus::Active => 'Активный',
                                                    ProductStatus::Decline => 'Отклонён',
                                                } }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <span class="meta-field">{{ $dish->created_at?->format('d.m.Y H:i') }}</span>
                            </div>

                            <div class="computed-totals">
                                На 100г (пересчитывается при сохранении): {{ $dish->calories }} ккал ·
                                Б {{ $dish->proteins }} · Ж {{ $dish->fats }} · У {{ $dish->carbs }} ·
                                итого {{ $dish->total_grams }} г
                            </div>

                            <div class="ingredients-title">Состав</div>
                            <div class="ingredients-list" data-ingredients>
                                @foreach ($dish->ingredients as $index => $ingredient)
                                    <div class="ingredient-row" data-ingredient-row>
                                        <input class="field-input field-input--ing-name" type="text" name="ingredients[{{ $index }}][name]" value="{{ $ingredient->name }}" placeholder="Название" required>
                                        <input class="field-input field-input--ing-macro" type="number" name="ingredients[{{ $index }}][proteins]" value="{{ $ingredient->proteins }}" placeholder="Б" min="0" step="0.01" required>
                                        <input class="field-input field-input--ing-macro" type="number" name="ingredients[{{ $index }}][fats]" value="{{ $ingredient->fats }}" placeholder="Ж" min="0" step="0.01" required>
                                        <input class="field-input field-input--ing-macro" type="number" name="ingredients[{{ $index }}][carbs]" value="{{ $ingredient->carbs }}" placeholder="У" min="0" step="0.01" required>
                                        <input class="field-input field-input--ing-macro" type="number" name="ingredients[{{ $index }}][grams]" value="{{ $ingredient->grams }}" placeholder="Грамм" min="0.01" step="0.01" required>
                                        <input type="hidden" name="ingredients[{{ $index }}][product_uuid]" value="{{ $ingredient->product_uuid }}">
                                        <button type="button" class="ingredient-remove-btn" data-remove-ingredient title="Удалить ингредиент">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="ingredient-add-btn" data-add-ingredient data-form-id="dish-form-{{ $dish->id }}">+ добавить ингредиент</button>
                        </form>

                        <div class="dish-actions">
                            <button type="submit" form="dish-form-{{ $dish->id }}" class="action-btn save-btn" title="Сохранить">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                            </button>

                            @if ($currentStatus === ProductStatus::Draft->value)
                                <form method="POST" action="{{ route('admin.dishes.approve', $dish, false) }}">
                                    @csrf
                                    <button type="submit" class="action-btn approve-btn" title="Одобрить">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.dishes.decline', $dish, false) }}">
                                    @csrf
                                    <button type="submit" class="action-btn decline-btn" title="Отклонить">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </form>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('admin.dishes.destroy', $dish, false) }}"
                                onsubmit="return confirm('Удалить «{{ $dish->name }}»?')"
                            >
                                @csrf
                                <button type="submit" class="action-btn delete-btn" title="Удалить">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $dishes->links('nutrition::admin.partials.pagination') }}
        @else
            <div class="empty">
                @if ($search !== '')
                    Ничего не найдено по запросу «{{ $search }}»
                @else
                    Нет блюд в этой категории
                @endif
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var addBtn = event.target.closest('[data-add-ingredient]');
            if (addBtn) {
                var form = document.getElementById(addBtn.dataset.formId);
                var list = form.querySelector('[data-ingredients]');
                var index = list.querySelectorAll('[data-ingredient-row]').length;
                var row = document.createElement('div');
                row.className = 'ingredient-row';
                row.setAttribute('data-ingredient-row', '');
                row.innerHTML =
                    '<input class="field-input field-input--ing-name" type="text" name="ingredients[' + index + '][name]" placeholder="Название" required>' +
                    '<input class="field-input field-input--ing-macro" type="number" name="ingredients[' + index + '][proteins]" placeholder="Б" min="0" step="0.01" required>' +
                    '<input class="field-input field-input--ing-macro" type="number" name="ingredients[' + index + '][fats]" placeholder="Ж" min="0" step="0.01" required>' +
                    '<input class="field-input field-input--ing-macro" type="number" name="ingredients[' + index + '][carbs]" placeholder="У" min="0" step="0.01" required>' +
                    '<input class="field-input field-input--ing-macro" type="number" name="ingredients[' + index + '][grams]" placeholder="Грамм" min="0.01" step="0.01" required>' +
                    '<input type="hidden" name="ingredients[' + index + '][product_uuid]" value="">' +
                    '<button type="button" class="ingredient-remove-btn" data-remove-ingredient title="Удалить ингредиент">✕</button>';
                list.appendChild(row);
                return;
            }

            var removeBtn = event.target.closest('[data-remove-ingredient]');
            if (removeBtn) {
                var thisRow = removeBtn.closest('[data-ingredient-row]');
                var thisList = removeBtn.closest('[data-ingredients]');
                if (thisList.querySelectorAll('[data-ingredient-row]').length > 1) {
                    thisRow.remove();
                }
            }
        });
    </script>
</body>
</html>
