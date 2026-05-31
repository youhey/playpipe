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

    const setPlayerState = (state) => {
        player.classList.toggle('is-idle', state === 'idle');
        player.classList.toggle('is-playing', state === 'playing');
        player.classList.toggle('is-paused', state === 'paused');
        playButton.textContent = state === 'playing' ? 'Ⅱ' : '▷';
        playButton.setAttribute('aria-label', state === 'playing' ? 'Pause episode' : 'Play episode');
    };

    let hasStartedPlayback = false;

    setPlayerState('idle');

    playButton.addEventListener('click', () => {
        if (audio.paused) {
            void audio.play();

            return;
        }

        audio.pause();
    });

    audio.addEventListener('play', () => {
        hasStartedPlayback = true;
        setPlayerState('playing');
    });

    audio.addEventListener('pause', () => {
        setPlayerState(hasStartedPlayback ? 'paused' : 'idle');
    });

    audio.addEventListener('ended', () => {
        hasStartedPlayback = false;
        setPlayerState('idle');
    });

    audio.addEventListener('loadedmetadata', () => {
        if (duration instanceof HTMLElement && audio.duration > 0) {
            duration.textContent = formatTime(audio.duration);
        }
    });
});
