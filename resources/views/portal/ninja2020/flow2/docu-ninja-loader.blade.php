<div class="rounded-lg border bg-card text-card-foreground shadow-sm overflow-hidden px-4 py-5 bg-white sm:gap-4 sm:px-6"
     wire:key="docuninja-loader" wire:init="loadDocuNinjaData">
    
     @if($error)
        <div class="alert alert-error">
            <ul>
                <li class="text-sm">{{ $error }}</li>
            </ul>
        </div>
    @endif

    {{-- Loading State --}}
    @if($isLoading)
        <div class="flex flex-col items-center justify-center p-8 space-y-4">
            {{-- Animated Spinner --}}
            <div class="relative">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-6 h-6 bg-blue-100 rounded-full animate-pulse"></div>
                </div>
            </div>
            
            {{-- Loading Steps --}}
            <div class="text-xs text-gray-500 space-y-1">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-lg font-medium text-gray-900">DocuNinja Loading...</span>
                </div>
            </div>
            
        </div>
    @endif

    {{-- Ready State - Show Success Message --}}
    @if($isReady && !$error)
        <div class="transition-all duration-500 ease-in-out">
            {{-- Success Message --}}
            <div class="flex flex-col items-center justify-center p-8 space-y-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">DocuNinja Ready!</h3>
                    <p class="text-sm text-gray-600">Switching to DocuNinja component...</p>
                </div>
                
                {{-- Loading indicator for transition --}}
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        </div>
    @endif
</div>
