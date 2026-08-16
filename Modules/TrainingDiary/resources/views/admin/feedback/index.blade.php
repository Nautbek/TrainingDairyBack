@php use App\Models\FeedbackThread; use App\Models\FeedbackMessage; @endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Обращения Training Diary — админка</title>
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
            background: #ececeb;
            color: #1b1b18;
        }

        .tab:hover { opacity: 0.85; }

        .tab-open.active { background: #dbeafe; color: #1e3a8a; border-color: #93c5fd; }
        .tab-closed.active { background: #e5e5e3; color: #44403c; border-color: #d4d4d4; }
        .tab.active { font-weight: 600; }
        .tab:not(.active) { opacity: 0.75; }

        .thread-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .thread-item {
            background: #fff;
            border: 1px solid #e5e5e3;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .thread-item.saved {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .thread-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #a8a7a4;
            margin-bottom: 0.75rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .status-open { background: #dbeafe; color: #1e3a8a; }
        .status-closed { background: #e5e5e3; color: #44403c; }

        .messages {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .message {
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            max-width: 85%;
            white-space: pre-wrap;
        }

        .message-user {
            background: #f5f5f3;
            align-self: flex-start;
        }

        .message-admin {
            background: #dbeafe;
            align-self: flex-end;
            margin-left: auto;
        }

        .message-sender {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #a8a7a4;
            margin-bottom: 0.125rem;
        }

        .message-time {
            font-size: 0.625rem;
            color: #a8a7a4;
            margin-top: 0.125rem;
        }

        .answer-form {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            border-top: 1px solid #f0f0ef;
            padding-top: 0.75rem;
        }

        .answer-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .field-select,
        .field-textarea {
            border: 1px solid #e5e5e3;
            border-radius: 0.25rem;
            font-size: 0.8125rem;
            font-family: inherit;
            background: #fff;
            color: #1b1b18;
            padding: 0.375rem 0.5rem;
        }

        .field-select:focus,
        .field-textarea:focus {
            outline: none;
            border-color: #a8a7a4;
        }

        .field-textarea {
            width: 100%;
            resize: vertical;
            min-height: 3.5rem;
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
            align-self: flex-start;
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
        <h1>Обращения — Training Diary</h1>

        @if (session('saved_thread_id'))
            <div class="flash flash-success">Обращение №{{ session('saved_thread_id') }} сохранено</div>
        @endif

        @if ($errors->any())
            <div class="flash flash-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <nav class="tabs">
            <a
                href="{{ route('trainingdiary.admin.feedback.index', [], false) }}"
                class="tab {{ $currentStatus === null ? 'active' : '' }}"
            >Все</a>

            @foreach ([FeedbackThread::STATUS_OPEN => 'Открыты', FeedbackThread::STATUS_CLOSED => 'Закрыты'] as $value => $label)
                <a
                    href="{{ route('trainingdiary.admin.feedback.index', ['status' => $value], false) }}"
                    class="tab tab-{{ $value }} {{ $currentStatus === $value ? 'active' : '' }}"
                >
                    {{ $label }}
                    @if ($counts->has($value))
                        ({{ $counts[$value] }})
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($threads->total() > 0)
            <div class="list-meta">
                {{ $threads->firstItem() }}–{{ $threads->lastItem() }} из {{ $threads->total() }}
            </div>

            <div class="thread-list">
                @foreach ($threads as $thread)
                    @php
                        $editing = (int) old('_thread_id') === $thread->id;
                        $statusValue = $editing ? old('status', $thread->status) : $thread->status;
                        $replyValue = $editing ? old('reply') : '';
                    @endphp
                    <div @class([
                        'thread-item',
                        'saved' => session('saved_thread_id') === $thread->id,
                    ])>
                        <div class="thread-meta">
                            <span>#{{ $thread->id }}</span>
                            <span class="status-badge status-{{ $thread->status }}">
                                {{ $thread->status === FeedbackThread::STATUS_OPEN ? 'Открыт' : 'Закрыт' }}
                            </span>
                            <span>{{ $thread->created_at?->format('d.m.Y H:i') }}</span>
                            @if ($thread->visit_ip)
                                <span>{{ $thread->visit_ip }}</span>
                            @endif
                            @if ($thread->user_id)
                                <span>user #{{ $thread->user_id }}</span>
                            @endif
                            @if ($thread->device_info)
                                <span title="{{ $thread->device_info }}">📱 {{ \Illuminate\Support\Str::limit($thread->device_info, 40) }}</span>
                            @endif
                        </div>

                        <div class="messages">
                            @foreach ($thread->messages->sortBy('created_at') as $message)
                                <div>
                                    <div class="message-sender">{{ $message->sender === FeedbackMessage::SENDER_ADMIN ? 'Админ' : 'Пользователь' }}</div>
                                    <div class="message message-{{ $message->sender }}">{{ $message->body }}</div>
                                    <div class="message-time">{{ $message->created_at?->format('d.m.Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>

                        <form
                            class="answer-form"
                            method="POST"
                            action="{{ route('trainingdiary.admin.feedback.update', $thread, false) }}"
                        >
                            @csrf
                            <input type="hidden" name="_thread_id" value="{{ $thread->id }}">

                            <div class="answer-row">
                                <select class="field-select" name="status">
                                    <option value="{{ FeedbackThread::STATUS_OPEN }}" @selected($statusValue === FeedbackThread::STATUS_OPEN)>Открыт</option>
                                    <option value="{{ FeedbackThread::STATUS_CLOSED }}" @selected($statusValue === FeedbackThread::STATUS_CLOSED)>Закрыт</option>
                                </select>
                            </div>
                            @if ($editing && $errors->has('status'))
                                <div class="field-error">{{ $errors->first('status') }}</div>
                            @endif

                            <textarea
                                class="field-textarea"
                                name="reply"
                                placeholder="Ответ пользователю..."
                            >{{ $replyValue }}</textarea>
                            @if ($editing && $errors->has('reply'))
                                <div class="field-error">{{ $errors->first('reply') }}</div>
                            @endif

                            <button type="submit" class="save-btn">Сохранить</button>
                        </form>
                    </div>
                @endforeach
            </div>

            {{ $threads->links('trainingdiary::admin.partials.pagination') }}
        @else
            <div class="empty">Нет обращений в этой категории</div>
        @endif
    </div>
</body>
</html>
