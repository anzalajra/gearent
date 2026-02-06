<div x-data="{
    showScanner: false,
    scanner: null,
    scanResult: null,
    initScanner() {
        this.showScanner = true;
        this.$nextTick(() => {
            if (!this.scanner) {
                // Load script if not loaded
                if (typeof Html5QrcodeScanner === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js';
                    script.onload = () => this.startScanner();
                    document.head.appendChild(script);
                } else {
                    this.startScanner();
                }
            } else {
                this.startScanner();
            }
        });
    },
    startScanner() {
        this.scanner = new Html5QrcodeScanner(
            'reader',
            { fps: 10, qrbox: { width: 250, height: 250 } },
            false
        );
        this.scanner.render(this.onScanSuccess.bind(this), this.onScanFailure);
    },
    onScanSuccess(decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`, decodedResult);
        this.scanResult = decodedText;
        this.stopScanner();
        
        try {
            const url = new URL(decodedText);
            // Verify it's a system URL (same origin)
            if (url.origin === window.location.origin) {
                 window.location.href = decodedText;
            } else {
                // Also allow if it's a relative path starting with /admin
                if (decodedText.startsWith(window.location.origin)) {
                    window.location.href = decodedText;
                } else {
                    new FilamentNotification()
                        .title('Invalid QR Code')
                        .body('QR Code must be from this system.')
                        .danger()
                        .send();
                }
            }
        } catch (e) {
             new FilamentNotification()
                .title('Scan Error')
                .body('Invalid QR Code format')
                .danger()
                .send();
        }
    },
    onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    },
    stopScanner() {
        if (this.scanner) {
            this.scanner.clear().then(() => {
                this.showScanner = false;
                this.scanner = null;
            }).catch((error) => {
                console.error('Failed to clear scanner', error);
                this.showScanner = false;
            });
        } else {
             this.showScanner = false;
        }
    }
}"
class="flex items-center"
>
    <button
        @click="initScanner()"
        type="button"
        class="flex items-center justify-center w-10 h-10 text-gray-500 transition hover:text-primary-500 focus:outline-none dark:text-gray-400 dark:hover:text-primary-400"
        title="Scan QR Code"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
        </svg>
    </button>

    <!-- Modal -->
    <div
        x-show="showScanner"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        x-transition
    >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 relative dark:bg-gray-800" @click.away="stopScanner()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Scan QR Code</h3>
                <button @click="stopScanner()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <span class="sr-only">Close</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div id="reader" width="100%"></div>
            
            <p class="mt-4 text-sm text-gray-500 text-center dark:text-gray-400">
                Point your camera at a system QR code to scan.
            </p>
        </div>
    </div>
</div>
