@props(['cardPreview'])

@if($cardPreview)
<!-- Social Card Preview Modal -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeCardPreview()"
>
    <div
        class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden"
        @click.stop
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-[--color-sambal-red] to-[--color-teh-tarik-brown-light]">
            <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
                Share Your Vibe
            </h2>
            <button
                wire:click="closeCardPreview"
                class="text-white hover:text-gray-200 transition-colors"
                aria-label="Close modal"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Card Preview -->
        <div class="p-6 bg-gray-50">
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <img
                    src="{{ $cardPreview['url'] }}"
                    alt="Social Media Card"
                    class="w-full h-auto"
                    loading="lazy"
                >
            </div>

            <!-- Share Actions -->
            <div class="space-y-4">
                <p class="text-center text-gray-600 text-sm font-medium">
                    Share this recommendation with your friends!
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Download Button -->
                    <a
                        href="{{ $cardPreview['url'] }}"
                        download="makanguru-recommendation.svg"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-[--color-pandan-green] text-white rounded-lg hover:bg-[--color-pandan-green-light] transition-colors font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download
                    </a>

                    <!-- Copy Link Button -->
                    <button
                        onclick="copyToClipboard({{ json_encode($cardPreview['url']) }})"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-[--color-sky-blue] text-white rounded-lg hover:bg-[--color-sky-blue-light] transition-colors font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copy Link
                    </button>

                    <!-- Share on WhatsApp Button -->
                    <a
                        href="https://wa.me/?text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!%20{{ urlencode($cardPreview['url']) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-[#25D366] text-white rounded-lg hover:bg-[#1DA851] transition-colors font-medium"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                        </svg>
                        WhatsApp
                    </a>
                </div>

                <!-- Alternative share options -->
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-xs text-center text-gray-500 mb-2">Or share on:</p>
                    <div class="flex justify-center gap-4">
                        <!-- Facebook -->
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($cardPreview['url']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-[#1877F2] hover:text-[#0d5dbf] transition-colors"
                            aria-label="Share on Facebook"
                        >
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>

                        <!-- Twitter/X -->
                        <a
                            href="https://twitter.com/intent/tweet?text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!&url={{ urlencode($cardPreview['url']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-[#000000] hover:text-[#333333] transition-colors"
                            aria-label="Share on Twitter/X"
                        >
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>

                        <!-- Telegram -->
                        <a
                            href="https://t.me/share/url?url={{ urlencode($cardPreview['url']) }}&text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-[#0088cc] hover:text-[#006699] transition-colors"
                            aria-label="Share on Telegram"
                        >
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-100 text-center">
            <p class="text-xs text-gray-500">
                Generated by <span class="font-semibold text-[--color-sambal-red]">MakanGuru</span> -
                AI-Powered Malaysian Food Recommendations
            </p>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        alert('Link copied to clipboard!');
    }, function(err) {
        console.error('Could not copy text: ', err);
        alert('Failed to copy link. Please try again.');
    });
}
</script>
@endif
