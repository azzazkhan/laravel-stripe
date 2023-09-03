<x-layout class="flex flex-col items-center justify-center p-20">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-lg shadow">
        <div class="flex items-center justify-center w-full h-20 text-white rounded-t-lg bg-emerald-600">
            <h4 class="text-xl font-bold">Purchase Successful</h4>
        </div>
        <div class="flex flex-col p-6 space-y-6 text-center text-gray-600">
            <p>
                Your purchase of <strong class="font-bold">${{ $amount }}</strong> against
                <strong class="text-bold">{{ $package->name }}</strong> pack was completed
                successfully.
            </p>

            <p class="text-sm">
                The amount of <strong class="font-bold">{{ $package->coins }} coins</strong>
                @if ($package->additional)
                    with an additional
                    <strong class="font-bold">{{ $package->additional }} coins</strong>
                @endif
                will be credited to your account. Thanks for being awesome!
            </p>
        </div>
    </div>

    <a
        href="{{ route('home') }}"
        role="button"
        class="inline-flex items-center px-6 mt-6 transition-colors rounded-lg h-11 hover:bg-gray-300"
    >
        Back to Home
    </a>
</x-layout>
