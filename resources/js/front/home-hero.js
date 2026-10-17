const hasTransparentBackground = (image) => {
    const canvas = document.createElement('canvas');
    canvas.width = 32;
    canvas.height = 32;
    const context = canvas.getContext('2d', { willReadFrequently: true });

    if (!context) {
        return false;
    }

    try {
        context.drawImage(image, 0, 0, 32, 32);
        const pixels = context.getImageData(0, 0, 32, 32).data;
        let transparentPixels = 0;

        for (let index = 3; index < pixels.length; index += 4) {
            if (pixels[index] < 250) {
                transparentPixels += 1;
            }
        }

        return transparentPixels / (32 * 32) > 0.05;
    } catch {
        // Cross-origin images still use their natural dimensions for fitting.
        return false;
    }
};

document.querySelectorAll('[data-home-hero-image]').forEach((image) => {
    const frame = image.closest('[data-home-hero-frame]');
    if (!frame) {
        return;
    }

    const fitImage = () => {
        if (!image.naturalWidth || !image.naturalHeight) {
            frame.classList.add('is-image-error');
            image.hidden = true;
            return;
        }

        const ratio = image.naturalWidth / image.naturalHeight;
        const shouldContain = image.naturalWidth < 480 || image.naturalHeight < 480
            || ratio > 2.2 || hasTransparentBackground(image);
        frame.dataset.imageFit = shouldContain ? 'contain' : 'cover';
    };

    if (image.complete) {
        fitImage();
    } else {
        image.addEventListener('load', fitImage, { once: true });
        image.addEventListener('error', fitImage, { once: true });
    }
});
