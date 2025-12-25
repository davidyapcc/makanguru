@props(['cardPreview'])

@if($cardPreview)
<!-- Social Card Preview Modal -->
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 p-4"
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeCardPreview()"
    @click="$wire.closeCardPreview()"
>
    <div
        class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden relative"
        @click.stop
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <!-- Close Button (Top Right Corner) -->
        <button
            wire:click="closeCardPreview"
            class="absolute top-4 right-4 z-10 bg-white rounded-full p-2 shadow-lg text-gray-700 hover:text-[--color-sambal-red] hover:bg-gray-100 transition-all duration-200"
            aria-label="Close modal"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <!-- Header -->
        <div class="p-4 md:p-6 border-b border-gray-200 bg-gradient-to-r from-[--color-sambal-red] to-[--color-teh-tarik-brown-light]">
            <h2 class="text-lg md:text-xl font-bold text-center">
                📤 Share Your Recommendation
            </h2>
        </div>

        <!-- Card Preview -->
        <div class="p-4 md:p-6 bg-gradient-to-br from-gray-50 via-white to-[--color-nasi-lemak-cream]/30 max-h-[70vh] overflow-y-auto">
            <!-- Card Image with enhanced styling -->
            <div class="relative bg-white rounded-2xl shadow-xl overflow-hidden mb-4 md:mb-6 border border-gray-200 transform transition-transform hover:scale-[1.01]">
                <img
                    src="{{ $cardPreview['url'] }}"
                    alt="MakanGuru Food Recommendation"
                    class="w-full h-auto"
                    loading="eager"
                    onerror="console.error('Failed to load social card image:', this.src); this.parentElement.innerHTML = '<div class=\'p-8 text-center text-red-600\'><p class=\'font-bold mb-2\'>❌ Failed to load card preview</p><p class=\'text-sm\'>Image URL: ' + this.src + '</p></div>';"
                >
                <!-- Decorative corner accent -->
                <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-bl from-[--color-sambal-red]/10 to-transparent"></div>
            </div>

            <!-- Share Actions -->
            <div class="space-y-4">
                <div class="text-center">
                    <p class="text-gray-700 text-base font-bold mb-1">
                        Share this recommendation! 🎉
                    </p>
                    <p class="text-gray-500 text-xs">
                        Spread the food love with your friends
                    </p>
                </div>

                <!-- Primary Share Button (WhatsApp - Most Popular in Malaysia) -->
                <a
                    href="https://wa.me/?text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!%20{{ urlencode($cardPreview['url']) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group flex items-center justify-center gap-3 px-6 py-4 bg-gradient-to-r from-[#25D366] to-[#128C7E] text-white rounded-xl hover:from-[#1DA851] hover:to-[#0F7A6B] transition-all duration-300 font-bold text-base shadow-lg hover:shadow-xl transform hover:scale-[1.02]"
                >
                    <svg class="w-6 h-6 transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"></path>
                    </svg>
                    <span class="tracking-wide">Share on WhatsApp</span>
                </a>

                <!-- Social Media Platforms -->
                <div>
                    <p class="text-xs text-center text-gray-600 mb-2.5 font-medium">Or share on:</p>
                    <div class="grid grid-cols-4 gap-2">
                        <!-- Facebook -->
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($cardPreview['url']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border-2 border-gray-200 hover:border-[#1877F2] hover:bg-blue-50 rounded-lg transition-all duration-200 transform hover:scale-105 group"
                            aria-label="Share on Facebook"
                        >
                            <svg class="w-6 h-6 text-[#1877F2] transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span class="text-[9px] font-semibold text-gray-700">Facebook</span>
                        </a>

                        <!-- Twitter/X -->
                        <a
                            href="https://twitter.com/intent/tweet?text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!&url={{ urlencode($cardPreview['url']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border-2 border-gray-200 hover:border-black hover:bg-gray-50 rounded-lg transition-all duration-200 transform hover:scale-105 group"
                            aria-label="Share on Twitter/X"
                        >
                            <svg class="w-6 h-6 text-black transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <span class="text-[9px] font-semibold text-gray-700">X/Twitter</span>
                        </a>

                        <!-- Telegram -->
                        <a
                            href="https://t.me/share/url?url={{ urlencode($cardPreview['url']) }}&text=Check%20out%20this%20food%20recommendation%20from%20MakanGuru!"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border-2 border-gray-200 hover:border-[#0088cc] hover:bg-blue-50 rounded-lg transition-all duration-200 transform hover:scale-105 group"
                            aria-label="Share on Telegram"
                        >
                            <svg class="w-6 h-6 text-[#0088cc] transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            <span class="text-[9px] font-semibold text-gray-700">Telegram</span>
                        </a>

                        <!-- Instagram -->
                        <button
                            onclick="shareToInstagram({{ json_encode($cardPreview['url']) }})"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 bg-white border-2 border-gray-200 hover:border-[#E1306C] hover:bg-pink-50 rounded-lg transition-all duration-200 transform hover:scale-105 group"
                            aria-label="Share on Instagram"
                        >
                            <svg class="w-6 h-6 text-[#E1306C] transform group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                            <span class="text-[9px] font-semibold text-gray-700">Instagram</span>
                        </button>
                    </div>
                </div>

                <!-- Utility Actions (Separated) -->
                <div class="pt-3 border-t border-gray-200">
                    <p class="text-xs text-center text-gray-600 mb-2.5 font-medium">Quick actions:</p>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Download -->
                        <a
                            href="{{ $cardPreview['url'] }}"
                            download="makanguru-recommendation.svg"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-500 text-white rounded-lg hover:from-green-700 hover:to-green-600 transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span class="text-sm font-semibold">Download</span>
                        </a>

                        <!-- Copy Link -->
                        <button
                            onclick="copyToClipboard({{ json_encode($cardPreview['url']) }})"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-500 text-white rounded-lg hover:from-blue-700 hover:to-blue-600 transition-all duration-200 font-medium shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm font-semibold">Copy Link</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 bg-gradient-to-r from-gray-50 to-gray-100 text-center border-t border-gray-200">
            <p class="text-xs text-gray-600">
                Generated by <span class="font-bold text-[--color-sambal-red]">MakanGuru</span> • AI-Powered Malaysian Food Recommendations
            </p>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Social card sharing functions (global scope)
if (typeof window.copyToClipboard === 'undefined') {
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('✓ Link copied to clipboard!', 'success');
        }, function(err) {
            console.error('Could not copy text: ', err);
            showToast('✗ Failed to copy link. Please try again.', 'error');
        });
    };
}

if (typeof window.shareToInstagram === 'undefined') {
    window.shareToInstagram = function(imageUrl) {
        // Detect if user is on mobile
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        if (isMobile) {
            // On mobile: Try to open Instagram app, then download as fallback
            // First, download the image
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = 'makanguru-recommendation.jpg';
            link.click();

            // Try to open Instagram app after a short delay
            setTimeout(() => {
                // Instagram app deep link
                const instagramUrl = 'instagram://library?AssetPath=' + encodeURIComponent(imageUrl);
                const instagramWebUrl = 'https://www.instagram.com/';

                // Try to open Instagram app
                window.location.href = instagramUrl;

                // Fallback to web version if app not installed
                setTimeout(() => {
                    window.open(instagramWebUrl, '_blank');
                }, 1500);
            }, 500);

            showToast('📸 Image saved! Opening Instagram app...', 'info', 4000);
        } else {
            // On desktop: Download the image and show instructions
            const link = document.createElement('a');
            link.href = imageUrl;
            link.download = 'makanguru-recommendation.svg';
            link.click();

            // Show detailed instructions with better formatting
            showInstagramInstructions();
        }
    };

    // Helper function to show Instagram sharing instructions
    window.showInstagramInstructions = function() {
        // Create custom modal for Instagram instructions
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10000;
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 400px;
            width: 90vw;
        `;

        modal.innerHTML = `
            <div style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📸</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #1F2937; margin-bottom: 1rem;">Share to Instagram</h3>
                <div style="text-align: left; color: #4B5563; font-size: 0.875rem; line-height: 1.5; margin-bottom: 1.5rem;">
                    <p style="margin-bottom: 0.75rem;"><strong>✅ Image downloaded!</strong></p>
                    <p style="margin-bottom: 0.5rem;">To share on Instagram:</p>
                    <ol style="padding-left: 1.25rem; margin-bottom: 1rem;">
                        <li style="margin-bottom: 0.5rem;">Open the <strong>Instagram app</strong> on your phone</li>
                        <li style="margin-bottom: 0.5rem;">Tap the <strong>+</strong> button to create a new post</li>
                        <li style="margin-bottom: 0.5rem;">Select the downloaded image from your gallery</li>
                        <li>Add your caption and share! 🎉</li>
                    </ol>
                    <p style="font-size: 0.75rem; color: #6B7280; font-style: italic;">
                        💡 Tip: You can also share to Instagram Stories for more engagement!
                    </p>
                </div>
                <button
                    onclick="this.parentElement.parentElement.remove()"
                    style="background: linear-gradient(to right, #E1306C, #F77737); color: white; padding: 0.75rem 2rem; border-radius: 0.5rem; border: none; font-weight: 600; cursor: pointer; width: 100%; font-size: 0.875rem;"
                >
                    Got it! 👍
                </button>
            </div>
        `;

        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        `;
        backdrop.onclick = () => {
            backdrop.remove();
            modal.remove();
        };

        document.body.appendChild(backdrop);
        document.body.appendChild(modal);
    };
}

if (typeof window.showToast === 'undefined') {
    window.showToast = function(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            info: 'bg-blue-500'
        };

        // Create toast with proper styling
        toast.style.cssText = `
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            color: white;
            max-width: 90vw;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0;
            transition: all 0.3s ease-out;
        `;

        // Set background color
        const bgColors = {
            success: '#10B981',
            error: '#EF4444',
            info: '#3B82F6'
        };
        toast.style.backgroundColor = bgColors[type];

        // Create icon SVG
        const iconPaths = {
            success: 'M5 13l4 4L19 7',
            error: 'M6 18L18 6M6 6l12 12',
            info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        };

        toast.innerHTML = `
            <svg style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPaths[type]}"></path>
            </svg>
            <span style="line-height: 1.25;">${message}</span>
        `;

        document.body.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            }, 10);
        });

        // Remove after duration
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(20px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };
}
</script>
@endpush
