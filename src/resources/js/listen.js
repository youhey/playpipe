const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '--:--';
    }

    const rounded = Math.floor(seconds);
    const minutes = Math.floor(rounded / 60).toString().padStart(2, '0');
    const remainingSeconds = (rounded % 60).toString().padStart(2, '0');

    return `${minutes}:${remainingSeconds}`;
};

document.querySelectorAll('[data-listen-player]').forEach((player) => {
    const audio = player.parentElement?.querySelector('[data-listen-audio]');
    const playButton = player.querySelector('[data-listen-play]');
    const duration = player.querySelector('[data-listen-duration]');

    if (!(audio instanceof HTMLAudioElement) || !(playButton instanceof HTMLButtonElement)) {
        return;
    }

    const setPlaying = (isPlaying) => {
        player.classList.toggle('is-playing', isPlaying);
        playButton.textContent = isPlaying ? 'Ⅱ' : '▷';
        playButton.setAttribute('aria-label', isPlaying ? 'Pause episode' : 'Play episode');
    };

    playButton.addEventListener('click', () => {
        if (audio.paused) {
            void audio.play();

            return;
        }

        audio.pause();
    });

    audio.addEventListener('play', () => setPlaying(true));
    audio.addEventListener('pause', () => setPlaying(false));
    audio.addEventListener('ended', () => setPlaying(false));

    audio.addEventListener('loadedmetadata', () => {
        if (duration instanceof HTMLElement && audio.duration > 0) {
            duration.textContent = formatTime(audio.duration);
        }
    });
});
