class YandexMusicTracker {
    constructor(yaToken) {
        this.yaToken = yaToken;
        this.currentTrack = null;
        this.initializePusher();
    }

    initializePusher() {
        // Инициализация Echo уже сделана в app.js
        this.channel = window.Echo.channel('yandex-music');

        this.channel.listen('.track-updated', (data) => {
            this.handleTrackUpdate(data);
        });
    }

    async startTracking() {
        try {
            const response = await fetch('/api/yandex-music/start-tracking', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    interval: 2
                })
            });

            if (!response.ok) {
                throw new Error('Failed to start tracking');
            }

            return await response.json();
        } catch (error) {
            console.error('Tracking error:', error);
            throw error;
        }
    }

    handleTrackUpdate(data) {
        if (!this.currentTrack || this.currentTrack.track.id !== data.track.id) {
            console.log('Track changed:', data.track.title);
            this.currentTrack = data;
            this.updateUI(data);
        } else if (this.currentTrack.progress_ms !== data.progress_ms) {
            this.currentTrack = data;
            this.updateProgress(data);
        }
    }

    updateUI(data) {
        // Обновляем интерфейс
        const playerElement = document.getElementById('yandex-music-player');
        if (playerElement) {
            playerElement.innerHTML = `
<div class="flex items-center space-x-4 mb-6">
            <img src="${data.track.image_url}" alt="Обложка" class="w-24 h-24 rounded-lg">
            <div>
                <h2 class="text-xl font-semibold">${data.track.title}</h2>
                <p class="text-gray-600">${data.track.artists[0].name}</p>
                <p class="text-sm text-gray-500">Альбом: ${data.track.albums[0].title}</p>
            </div>
        </div>
        <div class="flex justify-between items-center text-sm text-gray-500">
            <span id="progress">${this.formatTime(data.progress_ms)}</span>
            <span id="duration">${this.formatTime(data.duration_ms)}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5 mt-1">
            <div class="bg-yellow-100 h-2.5 rounded-full" id="percentage"
                 style="width: ${data.progress_ms / data.duration_ms * 100}%"></div>
        </div>
                <div class="mt-6 flex justify-center space-x-4">
            <a href="https://music.yandex.ru/track/${ data.track.id }" target="_blank"
               class="px-4 py-2 bg-yellow-100 text-white rounded hover:bg-blue-700 transition">
                Слушать в Яндекс Музыке
            </a>
        </div>
            `;
        }
    }

    updateProgress(data) {
        /*const progressElement = document.querySelector('#yandex-music-player .progress input');
        if (progressElement) {
            progressElement.value = data.progress_ms;
        }*/

        const currentTimeElement = document.querySelector('#progress');
        if (currentTimeElement) {
            currentTimeElement.textContent = this.formatTime(data.progress_ms);
        }

        const currentPercentageElement = document.querySelector('#percentage');
        if (currentPercentageElement) {
            currentPercentageElement.style.width = `${data.progress_ms / data.duration_ms * 100}%`;
        }
    }

    formatTime(ms) {
        const seconds = Math.floor(ms / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
    }

    stopTracking() {
        if (this.channel) {
            this.channel.unbind();
            this.pusher.unsubscribe('yandex-music');
        }
    }
}

// Использование:
const yaToken = import.meta.env.YANDEX_TOKEN; // Получайте безопасно
const tracker = new YandexMusicTracker(yaToken);
tracker.startTracking().catch(error => {
    console.error('Failed to start tracking:', error);
});

// Для остановки:
// tracker.stopTracking();
