import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const formatBytes = (bytes) => {
    if (! Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
};

const parseJson = (payload) => {
    if (! payload) {
        return null;
    }

    try {
        return JSON.parse(payload);
    } catch (_error) {
        return null;
    }
};

const firstValidationError = (errors) => {
    if (! errors || typeof errors !== 'object') {
        return null;
    }

    const firstKey = Object.keys(errors)[0];
    if (! firstKey) {
        return null;
    }

    const value = errors[firstKey];
    if (Array.isArray(value) && value.length > 0) {
        return String(value[0]);
    }

    if (typeof value === 'string' && value.trim() !== '') {
        return value;
    }

    return null;
};

const uploadErrorMessage = (xhr) => {
    const contentType = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
    const responsePayload = contentType.includes('application/json') ? parseJson(xhr.responseText) : null;
    const validationMessage = firstValidationError(responsePayload?.errors);

    if (validationMessage) {
        return validationMessage;
    }

    if (typeof responsePayload?.message === 'string' && responsePayload.message.trim() !== '') {
        return responsePayload.message;
    }

    if (xhr.status === 413) {
        return 'Upload is too large for this server. Reduce file size or increase Nginx/Apache and PHP upload limits.';
    }

    if (xhr.status === 419) {
        return 'Upload failed because the session expired or payload was rejected. Refresh the page and try again.';
    }

    if (xhr.status === 422) {
        return 'Some fields are invalid. Check the form errors and submit again.';
    }

    if (xhr.status >= 500) {
        return 'Server error during upload. Check server logs and upload limits.';
    }

    return `Upload failed (${xhr.status}). Check file sizes and server limits, then try again.`;
};

const initContentTypeToggle = () => {
    document.querySelectorAll('form[data-upload-form]').forEach((form) => {
        const typeSelect = form.querySelector('select[name="content_type"]');
        if (! (typeSelect instanceof HTMLSelectElement)) {
            return;
        }

        const seriesFields = Array.from(form.querySelectorAll('[data-series-field]'));
        if (! seriesFields.length) {
            return;
        }

        const syncSeriesFields = () => {
            const isSeries = typeSelect.value === 'series';

            seriesFields.forEach((fieldWrap) => {
                fieldWrap.classList.toggle('hidden', ! isSeries);
            });
        };

        typeSelect.addEventListener('change', syncSeriesFields);
        syncSeriesFields();
    });
};

initContentTypeToggle();

const uploadForms = document.querySelectorAll('form[data-upload-form]');

uploadForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'));
        const selectedFiles = fileInputs.flatMap((input) => Array.from(input.files ?? []));

        if (selectedFiles.length === 0) {
            return;
        }

        event.preventDefault();

        const progressWrap = form.querySelector('[data-upload-progress-wrap]');
        const progressLabel = form.querySelector('[data-upload-progress-label]');
        const progressPercent = form.querySelector('[data-upload-progress-percent]');
        const progressBar = form.querySelector('[data-upload-progress-bar]');
        const submitButton = form.querySelector('[data-upload-submit]');

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = true;
            submitButton.classList.add('opacity-70', 'cursor-not-allowed');
        }

        if (progressWrap) {
            progressWrap.classList.remove('hidden');
        }

        const totalBytes = selectedFiles.reduce((sum, file) => sum + (file.size || 0), 0);
        if (progressLabel) {
            progressLabel.textContent = `Uploading ${selectedFiles.length} file(s) (${formatBytes(totalBytes)})`;
        }

        const xhr = new XMLHttpRequest();
        xhr.open((form.getAttribute('method') || 'POST').toUpperCase(), form.getAttribute('action') || window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', (progressEvent) => {
            if (! progressEvent.lengthComputable) {
                return;
            }

            const percent = Math.max(1, Math.round((progressEvent.loaded / progressEvent.total) * 100));
            const loadedText = formatBytes(progressEvent.loaded);
            const totalText = formatBytes(progressEvent.total);

            if (progressPercent) {
                progressPercent.textContent = `${percent}%`;
            }

            if (progressLabel) {
                progressLabel.textContent = `Uploading ${loadedText} / ${totalText}`;
            }

            if (progressBar) {
                progressBar.style.width = `${percent}%`;
            }
        });

        xhr.addEventListener('load', () => {
            const contentType = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
            const responseUrl = xhr.responseURL || '';

            if (xhr.status >= 200 && xhr.status < 400) {
                if (responseUrl && responseUrl !== window.location.href) {
                    window.location.assign(responseUrl);
                    return;
                }

                if (contentType.includes('text/html') && xhr.responseText) {
                    document.open();
                    document.write(xhr.responseText);
                    document.close();
                    return;
                }

                window.location.reload();
                return;
            }

            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
            }

            if (progressLabel) {
                progressLabel.textContent = uploadErrorMessage(xhr);
            }
        });

        xhr.addEventListener('error', () => {
            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-70', 'cursor-not-allowed');
            }

            if (progressLabel) {
                progressLabel.textContent = 'Upload failed due to a network or server error.';
            }
        });

        const formData = new FormData(form);
        xhr.send(formData);
    });
});
