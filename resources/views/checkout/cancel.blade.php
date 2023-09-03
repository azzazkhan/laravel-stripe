<x-layout class="flex flex-col items-center justify-center p-20">
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-lg shadow">
        <div class="flex items-center justify-center w-full h-20 text-white bg-orange-600 rounded-t-lg">
            <h4 class="text-xl font-bold">Order Cancelled</h4>
        </div>
        <div class="flex flex-col p-6 space-y-6 text-center text-gray-600">
            <p>
                Your purchase of <strong class="font-bold">${{ $amount }}</strong> against
                <strong class="text-bold">{{ $package }}</strong> pack was cancelled.
            </p>

            <p class="text-sm">
                If you faced any problems regarding purchase please feel free
                to get in touch with our
                <a href="/support" class="hover:underline underline-offset-2">customer care</a>
                service.
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
