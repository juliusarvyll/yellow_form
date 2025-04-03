<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Styles -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            color: #1a202c;
            background-color: #f7fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            padding: 2rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #e53e3e;
            margin-bottom: 1rem;
        }
        p {
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            font-weight: 500;
            color: white;
            background-color: #3182ce;
            border-radius: 0.25rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #2c5282;
        }
    </style>
</head>
<body>
    <div class="container">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#e53e3e" class="w-16 h-16 mx-auto mb-4" width="64" height="64">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <h1>Access Denied (403)</h1>
        <p>{{ $message ?? 'You do not have permission to access this resource.' }}</p>

        @auth
            @if(auth()->user()->hasRole('Dean') && auth()->user()->department_id)
                <a href="{{ url('/dean') }}" class="btn">Go to Dean Panel</a>
            @else
                <a href="{{ url('/') }}" class="btn">Return Home</a>
            @endif
        @else
            <a href="{{ url('/login') }}" class="btn">Login</a>
        @endauth
    </div>
</body>
</html>
