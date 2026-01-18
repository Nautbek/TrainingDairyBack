<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Отзывы пользователей - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    
    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background-color: #FDFDFC;
                color: #1b1b18;
                line-height: 1.6;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
                padding: 2rem 1rem;
            }
            
            .header {
                text-align: center;
                margin-bottom: 3rem;
            }
            
            .header h1 {
                font-size: 2rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }
            
            .header p {
                color: #706f6c;
                font-size: 0.9rem;
            }
            
            .feedback-item {
                background: white;
                border: 1px solid #e3e3e0;
                border-radius: 0.5rem;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                transition: box-shadow 0.2s;
            }
            
            .feedback-item:hover {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            }
            
            .feedback-text {
                font-size: 1rem;
                line-height: 1.7;
                margin-bottom: 1rem;
                color: #1b1b18;
            }
            
            .feedback-meta {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.875rem;
                color: #706f6c;
                padding-top: 1rem;
                border-top: 1px solid #e3e3e0;
            }
            
            .feedback-date {
                font-weight: 500;
            }
            
            .feedback-app {
                background: #f5f5f3;
                padding: 0.25rem 0.75rem;
                border-radius: 0.25rem;
                font-size: 0.8rem;
            }
            
            .pagination {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
                margin-top: 3rem;
                padding: 2rem 0;
            }
            
            .pagination a,
            .pagination span {
                padding: 0.5rem 1rem;
                border: 1px solid #e3e3e0;
                border-radius: 0.25rem;
                text-decoration: none;
                color: #1b1b18;
                transition: all 0.2s;
            }
            
            .pagination a:hover {
                background: #f5f5f3;
                border-color: #1b1b18;
            }
            
            .pagination .active span {
                background: #1b1b18;
                color: white;
                border-color: #1b1b18;
            }
            
            .pagination .disabled span {
                color: #706f6c;
                cursor: not-allowed;
                opacity: 0.5;
            }
            
            .empty-state {
                text-align: center;
                padding: 4rem 2rem;
                color: #706f6c;
            }
            
            .empty-state p {
                font-size: 1.1rem;
            }
        </style>
    @endif
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Отзывы пользователей</h1>
            <p>Что говорят о нашем приложении</p>
        </div>
        
        @if($feedbacks->count() > 0)
            <div class="feedback-list">
                @foreach($feedbacks as $feedback)
                    <div class="feedback-item">
                        <div class="feedback-text">
                            {{ $feedback->text }}
                        </div>
                        <div class="feedback-meta">
                            <span class="feedback-date">
                                {{ $feedback->visit_date->format('d.m.Y') }}
                            </span>
                            @if($feedback->app)
                                <span class="feedback-app">{{ $feedback->app }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="pagination">
                {{ $feedbacks->links() }}
            </div>
        @else
            <div class="empty-state">
                <p>Пока нет отзывов</p>
            </div>
        @endif
    </div>
</body>
</html>
