<div class="max-w-md mx-auto my-12 p-8 bg-white shadow-xl rounded-xl border border-gray-100">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Iniciar sesión</h2>
        <p class="text-sm text-gray-600 mt-2">Bienvenido de nuevo a Óptica Guzmán</p>
    </div>

    <form wire:submit="login" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input wire:model="email" type="email" id="email" required
                class="mt-1 block w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-[#71C229] focus:border-[#71C229] transition duration-150">
            @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input wire:model="password" type="password" id="password" required
                class="mt-1 block w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-[#71C229] focus:border-[#71C229] transition duration-150">
            @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input wire:model="remember" type="checkbox" id="remember"
                    class="h-4 w-4 text-[#71C229] focus:ring-[#71C229] border-gray-300 rounded transition duration-150">
                <label for="remember" class="ml-2 block text-sm text-gray-700">Recordarme</label>
            </div>
        </div>

        <button type="submit"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-[#71C229] hover:bg-[#5ea322] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#71C229] transition duration-150">
            ENTRAR
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-100 text-center">
        <p class="text-sm text-gray-600">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="font-bold text-[#71C229] hover:underline" wire:navigate>Regístrate aquí</a>
        </p>
    </div>
</div>
