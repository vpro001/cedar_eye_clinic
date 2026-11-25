/* Runs in <head>, before first paint: marks that scripts run, so motion can start from its first frame. */
document.documentElement.classList.add('js');
