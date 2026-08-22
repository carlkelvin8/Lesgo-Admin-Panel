<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - LesGo Admin</title>
    @vite(["resources/css/app.css"])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="login-shell min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="auth-shell-card rounded-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-halved text-purple-600 text-2xl"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-800">Two-Factor Verification</h1>
                <p class="text-sm text-gray-500 mt-2">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.2fa.verify.post') }}">
                @csrf
                <div class="mb-6">
                    <input type="text" name="code" required maxlength="30" placeholder="Enter code" autofocus
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-lg tracking-[0.3em] font-mono focus:border-blue-500 outline-none transition">
                </div>
                <button type="submit" class="w-full brand-button px-4 py-3 rounded-lg text-sm font-medium">
                    <i class="fas fa-arrow-right mr-1"></i> Verify
                </button>
            </form>
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('admin.login') }}" class="text-gray-400 hover:text-white text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to login
            </a>
        </div>
    </div>
</body>
</html>
