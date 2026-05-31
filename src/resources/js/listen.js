const formatTime = (seconds) => {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '--:--';
    }

    const rounded = Math.floor(seconds);
    const minutes = Math.floor(rounded / 60).toString().padStart(2, '0');
    const remainingSeconds = (rounded % 60).toString().padStart(2, '0');

    return `${minutes}:${remainingSeconds}`;
};

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const playbackLabels = {
    unplayed: 'UNPLAYED',
    in_progress: 'IN_PROGRESS',
    completed: 'COMPLETED',
};

document.querySelectorAll('[data-listen-player]').forEach((player) => {
    const audio = player.parentElement?.querySelector('[data-listen-audio]');
    const playButton = player.querySelector('[data-listen-play]');
    const timeDisplay = player.querySelector('[data-listen-duration]');
    const fallbackDurationSeconds = Number(player.dataset.durationSeconds);
    const resumeSeconds = Number(player.dataset.resumeSeconds);
    const playbackStartUrl = player.dataset.playbackStartUrl;
    const playbackProgressUrl = player.dataset.playbackProgressUrl;
    const playbackCompleteUrl = player.dataset.playbackCompleteUrl;
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

    const sendPlaybackRequest = (url, method, payload = {}, keepalive = false) => {
        if (!url || !csrfToken) {
            return Promise.resolve(null);
        }

        return fetch(url, {
            method,
            keepalive,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        }).then((response) => {
            if (!response.ok) {
                return null;
            }

            return response.json();
        }).then((data) => {
            const status = data?.playback?.status;

            if (typeof status === 'string') {
                updatePlaybackBadges(status);
                hasSentComplete = status === 'completed';
            }

            return data;
        }).catch(() => null);
    };

    const sendStart = () => {
        if (hasSentStart) {
            return;
        }

        hasSentStart = true;
        void sendPlaybackRequest(playbackStartUrl, 'POST');
    };

    const sendProgress = (force = false, keepalive = false) => {
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

        lastProgressSyncedAt = now;
        void sendPlaybackRequest(playbackProgressUrl, 'PATCH', playbackPayload(), keepalive);
    };

    const sendComplete = (keepalive = false) => {
        if (hasSentComplete) {
            return;
        }

        hasSentComplete = true;
        void sendPlaybackRequest(playbackCompleteUrl, 'POST', playbackPayload(), keepalive);
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

    window.addEventListener('pagehide', () => {
        if (!audio.paused && !audio.ended) {
            sendProgress(true, true);
        }
    });
});
