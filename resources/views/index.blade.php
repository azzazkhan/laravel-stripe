<x-layout class="p-10">
    <div class="grid max-w-3xl grid-cols-3 gap-10 mx-auto">
        @foreach ($packages as $package)
            <div class="flex flex-col p-2 space-y-4 transition-transform duration-300 border border-gray-200 rounded-lg hover:scale-105">
                <div class="relative flex items-center justify-center w-full bg-gray-200 rounded-md select-none h-28">
                    <h1 class="text-lg font-bold">{{ $package->name }}</h1>

                    @if ($package->additional)
                        <h4 class="absolute text-sm text-gray-600 transform -translate-x-1/2 translate-y-4 top-1/2 left-1/2">
                            + {{ $package->additional }}
                        </h4>
                    @endif

                </div>
                @auth
                    <button
                        type="button"
                        class="flex items-center justify-center w-full h-10 font-bold text-white transition-colors bg-blue-500 rounded-md hover:bg-blue-600"
                    >
                        Buy Now <span class="pl-1 text-xs">(${{ $package->price }})</span>
                    </button>
                @else
                    <a
                        role="button"
                        href="{{ route('login') }}"
                        class="flex items-center justify-center w-full font-bold transition-colors bg-gray-200 rounded-md h-9 hover:bg-gray-300"
                    >
                        Login
                    </a>
                @endauth
            </div>
        @endforeach
    </div>
</x-layout>
