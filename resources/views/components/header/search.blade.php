<form {{ $attributes->merge(['class' => 'w-full relative']) }}
      action="{{ route('search.view') }}">
    <input name="term"
           type="search"
           placeholder="¿Qué estás buscando?"
           class="w-full pl-6 pr-12 py-3 text-sm bg-black text-white border border-white rounded-full focus:ring-[#71C229] focus:border-[#71C229] placeholder-gray-400"
           value="{{ $this->term }}" />

    <button class="absolute p-2 text-white transition -translate-y-1/2 rounded-full right-3 top-1/2 hover:text-[#71C229]">
        <span class="sr-only">Submit Search</span>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-6 h-6"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>
</form>
