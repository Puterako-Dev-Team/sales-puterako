<!-- filepath: c:\laragon\www\sales-puterako\resources\views\auth\login.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="font-sans antialiased">
    <div class="min-h-screen flex">
        <!-- Left Side - Image -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-green-600 to-cyan-500 relative overflow-hidden">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white">
                    <h1 class="text-4xl font-bold mb-4">Sales Management System</h1>
                    <p class="text-xl opacity-90">PT. Puterako Inti Buana</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 bg-gray-50">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="text-center mb-12">
                    <img src="{{ asset('assets/puterako_logo.png') }}" alt="Puterako Logo"
                        class="mx-auto h-16 w-auto mb-4">
                    
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            autofocus autocomplete="username"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 @error('email') border-red-500 @enderror"
                            placeholder="Masukkan email Anda">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition duration-200 @error('password') border-red-500 @enderror"
                            placeholder="Masukkan password Anda">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember_me" type="checkbox" name="remember"
                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Masuk
                    </button>
                </form>

                <!-- Demo Accounts Info -->
                <div class="mt-8 p-4 bg-green-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-green-900 mb-2">Demo Accounts:</h3>
                    <div class="text-xs text-green-700 space-y-1">
                        <div><strong>Administrator:</strong> admin@puterako.com / password123</div>
                        <div><strong>Direktur:</strong> direktur@puterako.com / password123</div>
                        <div><strong>Manager:</strong> manager@puterako.com / password123</div>
                        <div><strong>Supervisor:</strong> supervisor@puterako.com / password123</div>
                        <div><strong>Staff:</strong> staff@puterako.com / password123</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
