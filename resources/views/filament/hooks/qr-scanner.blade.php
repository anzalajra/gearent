<div x-data="{
    showScanner: false,
    scanner: null,
    scanResult: null,
    isLoading: false,
    errorMessage: null,
    initScanner() {
        this.showScanner = true;
        this.isLoading = false;
        this.errorMessage = null;
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
        // Calculate responsive qrbox size
        const width = window.innerWidth > 600 ? 250 : 200;
        
        this.scanner = new Html5QrcodeScanner(
            'reader',
            { 
                fps: 10, 
                qrbox: { width: width, height: width },
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true
            },
            false
        );
        this.scanner.render(this.onScanSuccess.bind(this), this.onScanFailure);
    },
    onScanSuccess(decodedText, decodedResult) {
        if (this.isLoading) return; // Prevent multiple scans
        
        console.log(`Scan result: ${decodedText}`, decodedResult);
        this.isLoading = true;
        
        // Play beep sound (optional, simple implementation)
        // const audio = new Audio('/sounds/beep.mp3'); audio.play().catch(e => {});

        try {
            const url = new URL(decodedText);
            // Verify it's a system URL (same origin)
            if (url.origin === window.location.origin) {
                 this.scanResult = 'QR Code Valid! Redirecting...';
                 
                 new FilamentNotification()
                    .title('QR Code Detected')
                    .body('Redirecting to document...')
                    .success()
                    .send();

                 setTimeout(() => {
                     window.location.href = decodedText;
                 }, 1000);
            } else {
                this.handleError('Invalid QR Code: Origin mismatch. Must be from this system.');
            }
        } catch (e) {
             this.handleError('Invalid QR Code Format: Not a valid URL.');
        }
    },
    handleError(message) {
        this.isLoading = false;
        this.errorMessage = message;
        new FilamentNotification()
            .title('Scan Error')
            .body(message)
            .danger()
            .send();
            
        // Resume scanning after error delay if needed, or just let user try again
        // Currently html5-qrcode scanner pauses on success, so we might need to resume or re-render if we want continuous scanning.
        // But for this use case (redirect), stopping or showing error is fine.
    },
    onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    },
    stopScanner() {
        if (this.scanner) {
            this.scanner.clear().then(() => {
                this.showScanner = false;
                this.scanner = null;
                this.isLoading = false;
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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/75 backdrop-blur-sm transition-opacity"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div 
            class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 relative dark:bg-gray-800 transform transition-all"
            @click.away="stopScanner()"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Scan QR Code
                </h3>
                <button @click="stopScanner()" class="text-gray-400 hover:text-gray-500 focus:outline-none p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <span class="sr-only">Close</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900">
                <div id="reader" class="w-full"></div>
                
                <!-- Loading Overlay -->
                <div x-show="isLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-white/90 dark:bg-gray-800/90 z-10">
                    <svg class="animate-spin h-10 w-10 text-primary-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">QR Code Detected!</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Redirecting...</p>
                </div>
            </div>
            
            <div class="mt-4">
                <p x-show="!errorMessage" class="text-sm text-gray-500 text-center dark:text-gray-400">
                    Point your camera at a system QR code.
                </p>
                <p x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-500 text-center font-medium animate-pulse"></p>
            </div>
            
            <!-- Custom CSS to tidy up Html5QrcodeScanner -->
            <style>
                #reader__scan_region {
                    background: white;
                }
                #reader__dashboard_section_csr button {
                    background-color: #2563eb;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    font-size: 14px;
                    cursor: pointer;
                    margin-top: 10px;
                }
                #reader__dashboard_section_swaplink {
                    text-decoration: none;
                    color: #2563eb;
                    font-weight: bold;
                }
                #reader video {
                    object-fit: cover;
                    border-radius: 8px;
                }
            </style>
        </div>
    </div>
</div>
