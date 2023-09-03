<x-layout>
    <header class="flex items-center justify-between h-16 px-6 border-b border-gray-300">
        <a href="{{ route('home') }}" class="text-xl font-bold transition-colors hover:text-blue-600">
            {{ config('app.name') }}
        </a>

        @auth
            <div class="flex items-center space-x-2">
                <img src={{ Vite::asset('resources/images/coin.png') }} class="h-5" alt="coin" />
                <span class="font-bold">{{ Auth::user()->balance }}</span>
            </div>
        @else
            <a
                href="{{ route('register') }}"
                role="button"
                class="inline-flex items-center h-10 px-6 font-bold transition-colors rounded-lg hover:bg-blue-600 hover:text-white"
            >
                Register
            </a>
        @endauth
    </header>
    <div class="grid max-w-3xl grid-cols-3 mx-auto mt-6 gap-x-10 gap-y-6">
        @foreach ($packages as $package)
            <form
                action="{{ route('checkout.initiate') }}"
                method="POST"
                class="flex flex-col p-2 space-y-4 border border-gray-200 rounded-lg"
            >
                @csrf
                <input type="hidden" name="package" value="{{ $package->id }}" />
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
                        type="submit"
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
            </form>
        @endforeach
    </div>
</x-layout>
