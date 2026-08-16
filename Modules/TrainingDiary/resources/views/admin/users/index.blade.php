<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Скидки пользователей — админка</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem 3rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .section-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.8125rem;
        }

        .section-nav a {
            color: #706f6c;
            text-decoration: none;
        }

        .section-nav a.active {
            color: #1b1b18;
            font-weight: 600;
        }

        .list-meta {
            font-size: 0.8125rem;
            color: #706f6c;
            margin-bottom: 1rem;
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .search-form input[type="text"] {
            flex: 1;
            border: 1px solid #e5e5e3;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-family: inherit;
            padding: 0.5rem 0.75rem;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .search-form button,
        .search-form a.clear {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .search-form button {
            border: 1px solid #1b1b18;
            background: #1b1b18;
            color: #fff;
        }

        .search-form a.clear {
            border: 1px solid #e5e5e3;
            background: #fff;
            color: #1b1b18;
        }

        .user-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .user-item {
            background: #fff;
            border: 1px solid #e5e5e3;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .user-item.saved {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .user-item.discounted {
            border-color: #93c5fd;
        }

        .user-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .user-uuid {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.8125rem;
        }

        .user-app {
            font-size: 0.6875rem;
            color: #a8a7a4;
        }

        .discount-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 600;
            background: #dbeafe;
            color: #1e3a8a;
        }

        .discount-badge.free {
            background: #fef3c7;
            color: #92400e;
        }

        .discount-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .field-number {
            width: 5rem;
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
            font-family: inherit;
            background: #fff;
            color: #1b1b18;
            padding: 0.375rem 0.5rem;
        }

        .field-number:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .field-suffix {
            font-size: 0.8125rem;
            color: #706f6c;
        }

        .save-btn {
            padding: 0.375rem 0.875rem;
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid #1b1b18;
            background: #1b1b18;
            color: #fff;
        }

        .save-btn:hover { opacity: 0.9; }

        .field-error {
            font-size: 0.6875rem;
            color: #dc2626;
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
        <nav class="section-nav">
            <a href="{{ route('trainingdiary.admin.feedback.index', [], false) }}">Обращения</a>
            <a href="{{ route('trainingdiary.admin.users.index', [], false) }}" class="active">Скидки</a>
        </nav>

        <h1>Скидки пользователей</h1>

        @if (session('saved_user_id'))
            <div class="flash flash-success">Скидка сохранена</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="search-form" method="GET" action="{{ route('trainingdiary.admin.users.index', [], false) }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Поиск по uuid...">
            <button type="submit">Найти</button>
            @if ($search !== '')
                <a class="clear" href="{{ route('trainingdiary.admin.users.index', [], false) }}">Сбросить</a>
            @endif
        </form>

        @if ($users->total() > 0)
            <div class="list-meta">
                {{ $users->firstItem() }}–{{ $users->lastItem() }} из {{ $users->total() }}
            </div>

            <div class="user-list">
                @foreach ($users as $user)
                    @php
                        $editing = (int) old('_user_id') === $user->id;
                        $discountValue = $editing ? old('discount_percent') : $user->discount_percent;
                    @endphp
                    <div @class([
                        'user-item',
                        'saved' => session('saved_user_id') === $user->id,
                        'discounted' => $user->discount_percent > 0,
                    ])>
                        <div class="user-meta">
                            <span class="user-uuid">{{ $user->uuid }}</span>
                            @if ($lastApps->has($user->id))
                                <span class="user-app">{{ $lastApps[$user->id] }}</span>
                            @endif
                            @if ($user->discount_percent > 0)
                                <span @class(['discount-badge', 'free' => $user->discount_percent >= 100])>
                                    -{{ $user->discount_percent }}%{{ $user->discount_percent >= 100 ? ' (бесплатно)' : '' }}
                                </span>
                            @endif
                        </div>

                        <form
                            class="discount-form"
                            method="POST"
                            action="{{ route('trainingdiary.admin.users.discount', $user, false) }}"
                        >
                            @csrf
                            <input type="hidden" name="_user_id" value="{{ $user->id }}">
                            <input
                                class="field-number"
                                type="number"
                                name="discount_percent"
                                min="0"
                                max="100"
                                value="{{ $discountValue }}"
                            >
                            <span class="field-suffix">%</span>
                            <button type="submit" class="save-btn">Сохранить</button>
                        </form>
                        @if ($editing && $errors->has('discount_percent'))
                            <div class="field-error">{{ $errors->first('discount_percent') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{ $users->links('trainingdiary::admin.partials.pagination') }}
        @else
            <div class="empty">Пользователи не найдены</div>
        @endif
    </div>
</body>
</html>
