const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '--:--';
    }

    const rounded = Math.floor(seconds);
    const minutes = Math.floor(rounded / 60).toString().padStart(2, '0');
    const remainingSeconds = (rounded % 60).toString().padStart(2, '0');

    return `${minutes}:${remainingSeconds}`;
};

const playbackLabels = {
    unplayed: 'UNPLAYED',
    in_progress: 'IN_PROGRESS',
    completed: 'COMPLETED',
};

document.querySelectorAll('[data-listen-player]').forEach((player) => {
    const playerRoot = player.closest('[data-episode-player-root]') ?? player.parentElement;
    const audio = playerRoot?.querySelector('[data-listen-audio]');
    const playButton = player.querySelector('[data-listen-play]');
    const timeDisplay = player.querySelector('[data-listen-duration]');
    const fallbackDurationSeconds = Number(player.dataset.durationSeconds);
    const resumeSeconds = Number(player.dataset.resumeSeconds);
    const sectionList = document.querySelector('[data-section-list]');
    const playbackBadges = Array.from(document.querySelectorAll('[data-playback-badge]'));
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

    let playbackStatus = player.dataset.playbackStatus ?? 'unplayed';
    let hasStartedPlayback = false;
    let hasEnteredTracking = false;
    let hasSentStart = false;
    let hasSentComplete = playbackStatus === 'completed';
    let hasAppliedResume = false;
    let lastProgressSyncedAt = 0;
    let lastSyncedPositionSeconds = null;

    const livewireComponent = () => {
        const componentRoot = player.closest('[wire\\:id]');
        const componentId = componentRoot?.getAttribute('wire:id');

        if (!componentId || !window.Livewire?.find) {
            return null;
        }

        return window.Livewire.find(componentId);
    };

    const getKnownDuration = () => {
        if (Number.isFinite(audio.duration) && audio.duration > 0) {
            return audio.duration;
        }

        if (Number.isFinite(fallbackDurationSeconds) && fallbackDurationSeconds > 0) {
            return fallbackDurationSeconds;
        }

        return null;
    };

    const playbackPayload = () => {
        const knownDuration = getKnownDuration();
        const payload = {
            position_seconds: Math.max(0, Math.floor(audio.currentTime || 0)),
        };

        if (knownDuration !== null) {
            payload.duration_seconds = Math.max(0, Math.floor(knownDuration));
        }

        return payload;
    };

    const updatePlaybackBadges = (status) => {
        playbackStatus = status;

        playbackBadges.forEach((badge) => {
            badge.classList.remove('is-unplayed', 'is-in-progress', 'is-completed');
            badge.classList.add(`is-${status.replace('_', '-')}`);
            badge.textContent = playbackLabels[status] ?? status.toUpperCase();
        });
    };

    const callPlaybackAction = (method, ...params) => {
        const component = livewireComponent();

        if (!component?.$call) {
            return Promise.resolve(null);
        }

        return component.$call(method, ...params).then(() => {
            const status = typeof component.playbackStatus === 'string' ? component.playbackStatus : null;

            if (status !== null) {
                updatePlaybackBadges(status);
                hasSentComplete = status === 'completed';
            }

            return status;
        }).catch(() => null);
    };

    const sendStart = () => {
        if (hasSentStart) {
            return;
        }

        hasSentStart = true;
        const payload = playbackPayload();

        void callPlaybackAction('startPlayback', payload.position_seconds, payload.duration_seconds ?? null);
    };

    const sendProgress = (force = false) => {
        if (hasSentComplete || playbackStatus === 'completed') {
            return;
        }

        if (!force && audio.paused) {
            return;
        }

        const now = Date.now();

        if (!force && now - lastProgressSyncedAt < 5000) {
            return;
        }

        const payload = playbackPayload();

        if (!force && lastSyncedPositionSeconds !== null && Math.abs(payload.position_seconds - lastSyncedPositionSeconds) < 1) {
            return;
        }

        lastProgressSyncedAt = now;
        lastSyncedPositionSeconds = payload.position_seconds;
        void callPlaybackAction('syncProgress', payload.position_seconds, payload.duration_seconds ?? null);
    };

    const sendComplete = () => {
        if (hasSentComplete) {
            return;
        }

        hasSentComplete = true;
        const payload = playbackPayload();

        void callPlaybackAction('completePlayback', payload.position_seconds, payload.duration_seconds ?? null);
    };

    const applyResumePosition = () => {
        if (
            hasAppliedResume
            || playbackStatus !== 'in_progress'
            || !Number.isFinite(resumeSeconds)
            || resumeSeconds < 5
        ) {
            return;
        }

        const knownDuration = getKnownDuration();

        if (knownDuration !== null && resumeSeconds >= Math.max(0, knownDuration - 10)) {
            return;
        }

        audio.currentTime = resumeSeconds;
        hasAppliedResume = true;
        updateTimeDisplay(resumeSeconds);
        updateActiveSection(resumeSeconds);
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

    const completeIfNearEnd = () => {
        const knownDuration = getKnownDuration();

        if (audio.paused || knownDuration === null || audio.currentTime < knownDuration - 1) {
            return;
        }

        sendComplete();
    };

    setPlayerState('idle');
    updateTimeDisplay(0);

    playButton.addEventListener('click', () => {
        if (audio.paused) {
            if (audio.ended) {
                audio.currentTime = 0;
                updateTimeDisplay(0);
                updateActiveSection(0, true);
            } else {
                applyResumePosition();
            }

            void audio.play();

            return;
        }

        audio.pause();
    });

    audio.addEventListener('play', () => {
        applyResumePosition();
        hasStartedPlayback = true;
        hasEnteredTracking = true;
        sendStart();
        setPlayerState('playing');
        updateTimeDisplay();
        updateActiveSection();
    });

    audio.addEventListener('pause', () => {
        setPlayerState(hasStartedPlayback ? 'paused' : 'idle');
        updateTimeDisplay();
        updateActiveSection();

        if (!audio.ended) {
            sendProgress(true);
        }
    });

    audio.addEventListener('ended', () => {
        hasStartedPlayback = false;
        setPlayerState('idle');
        updateTimeDisplay(getKnownDuration() ?? audio.currentTime);
        updateActiveSection(getKnownDuration() ?? audio.currentTime, true);
        sendComplete();
    });

    audio.addEventListener('loadedmetadata', () => {
        applyResumePosition();
        updateTimeDisplay();
        updateActiveSection();
    });

    audio.addEventListener('timeupdate', () => {
        updateTimeDisplay();
        updateActiveSection();
        sendProgress();
        completeIfNearEnd();
    });

});
