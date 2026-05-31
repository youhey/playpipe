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
    const timeDisplay = player.querySelector('[data-listen-duration]');
    const fallbackDurationSeconds = Number(player.dataset.durationSeconds);
    const sectionList = document.querySelector('[data-section-list]');
    const sections = Array.from(document.querySelectorAll('[data-section]')).map((section) => ({
        element: section,
        startSeconds: Number(section.dataset.startSeconds),
        endSeconds: Number(section.dataset.endSeconds),
    })).filter((section) => (
        section.element instanceof HTMLElement
        && Number.isFinite(section.startSeconds)
        && Number.isFinite(section.endSeconds)
        && section.endSeconds > section.startSeconds
    ));

    if (!(audio instanceof HTMLAudioElement) || !(playButton instanceof HTMLButtonElement)) {
        return;
    }

    const getKnownDuration = () => {
        if (Number.isFinite(audio.duration) && audio.duration > 0) {
            return audio.duration;
        }

        if (Number.isFinite(fallbackDurationSeconds) && fallbackDurationSeconds > 0) {
            return fallbackDurationSeconds;
        }

        return null;
    };

    const updateTimeDisplay = (currentSeconds = audio.currentTime) => {
        if (!(timeDisplay instanceof HTMLElement)) {
            return;
        }

        const knownDuration = getKnownDuration();
        const safeCurrent = Number.isFinite(currentSeconds) && currentSeconds > 0 ? currentSeconds : 0;
        const cappedCurrent = knownDuration === null ? safeCurrent : Math.min(safeCurrent, knownDuration);
        const total = knownDuration === null ? '--:--' : formatTime(knownDuration);

        timeDisplay.textContent = `${formatTime(cappedCurrent)} / ${total}`;
    };

    const updateActiveSection = (currentSeconds = audio.currentTime, isTracking = hasEnteredTracking) => {
        if (!(sectionList instanceof HTMLElement) || sections.length === 0) {
            return;
        }

        sectionList.classList.toggle('is-tracking', isTracking);

        if (!isTracking) {
            sections.forEach(({ element }) => {
                element.classList.remove('is-active', 'is-dimmed');
            });

            return;
        }

        const safeCurrent = Number.isFinite(currentSeconds) && currentSeconds > 0 ? currentSeconds : 0;
        const activeSection = sections.find((section) => (
            safeCurrent >= section.startSeconds && safeCurrent < section.endSeconds
        )) ?? sections.at(-1);

        sections.forEach(({ element }) => {
            const isActive = element === activeSection?.element;

            element.classList.toggle('is-active', isActive);
            element.classList.toggle('is-dimmed', !isActive);
        });
    };

    const setPlayerState = (state) => {
        player.classList.toggle('is-idle', state === 'idle');
        player.classList.toggle('is-playing', state === 'playing');
        player.classList.toggle('is-paused', state === 'paused');
        playButton.textContent = state === 'playing' ? 'Ⅱ' : '▷';
        playButton.setAttribute('aria-label', state === 'playing' ? 'Pause episode' : 'Play episode');
    };

    let hasStartedPlayback = false;
    let hasEnteredTracking = false;

    setPlayerState('idle');
    updateTimeDisplay(0);

    playButton.addEventListener('click', () => {
        if (audio.paused) {
            if (audio.ended) {
                audio.currentTime = 0;
                updateTimeDisplay(0);
                updateActiveSection(0, true);
            }

            void audio.play();

            return;
        }

        audio.pause();
    });

    audio.addEventListener('play', () => {
        hasStartedPlayback = true;
        hasEnteredTracking = true;
        setPlayerState('playing');
        updateTimeDisplay();
        updateActiveSection();
    });

    audio.addEventListener('pause', () => {
        setPlayerState(hasStartedPlayback ? 'paused' : 'idle');
        updateTimeDisplay();
        updateActiveSection();
    });

    audio.addEventListener('ended', () => {
        hasStartedPlayback = false;
        setPlayerState('idle');
        updateTimeDisplay(getKnownDuration() ?? audio.currentTime);
        updateActiveSection(getKnownDuration() ?? audio.currentTime, true);
    });

    audio.addEventListener('loadedmetadata', () => {
        updateTimeDisplay();
        updateActiveSection();
    });

    audio.addEventListener('timeupdate', () => {
        updateTimeDisplay();
        updateActiveSection();
    });
});
