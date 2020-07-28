import React, { useState } from 'react';
import ReactCrop from 'react-image-crop';

import 'react-image-crop/lib/ReactCrop.scss';
import './EntrantModal.scss';

const IMAGE_WIDTH = 150;
const IMAGE_HEIGHT = 150;

const EntrantModal = ({
  entrant,
  onSubmit,
  onClose
}) => {
  const [ newEntrant, setNewEntrant ] = useState({ ...entrant });
  const [ image, setImage ] = useState(null);
  const [ crop, setCrop ] = useState({ aspect: 1, x: 0, y: 0, width: IMAGE_WIDTH, height: IMAGE_HEIGHT });
  const [ isDataDirty, setIsDataDirty ] = useState(false);
  const [ isCropDirty, setIsCropDirty ] = useState(false);

  const handleOverlayClick = evt => {
    if (evt.target.classList.contains('entrant-modal')) {
      onClose();
    }
  };

  const handleCropChange = newCrop => {
    // only mark the crop as dirty if something has changed from the initial render
    if (
      newCrop.x !== crop.x ||
      newCrop.y !== crop.y ||
      newCrop.width !== crop.width ||
      newCrop.height !== crop.height
    ) {
      setIsCropDirty(true);
    }
    setCrop(newCrop);
  };

  const handleFieldChange = evt => {
    setIsDataDirty(true);
    setNewEntrant({
      ...newEntrant,
      [evt.target.name]: evt.target.value,
    });
  };

  const handleSubmitClick = () => {
    // Ensure the crop coordinates are scaled correctly
    // depending on how the image was displayed
    if (isCropDirty) {
      const scaleX = image.naturalWidth / image.width;
      const scaleY = image.naturalHeight / image.height;
      crop.x *= scaleX;
      crop.y *= scaleY;
      crop.width *= scaleX;
      crop.height *= scaleY;
    }

    onSubmit(isDataDirty && newEntrant, isCropDirty && crop)
  };

  return (
    <div
      className='entrant-modal'
      onClick={handleOverlayClick}
    >
      <div className="entrant-modal__window">
        <div className="entrant-modal__crop">
          <ReactCrop
            src={entrant.image}
            className="entrant-modal__crop-image"
            crop={crop}
            minWidth={IMAGE_WIDTH}
            minHeight={IMAGE_HEIGHT}
            onChange={handleCropChange}
            onImageLoaded={setImage}
          />
        </div>
        <div className="entrant-modal__form">
          <div className="input-group">
            <label htmlFor="entrantName" className="input-group__label">Name</label>
            <input
              type="text"
              id="entrantName"
              name="name"
              className="input-group__text"
              value={newEntrant.name}
              onChange={handleFieldChange}
            />
          </div>
          <div className="input-group">
            <label htmlFor="entrantSource" className="input-group__label">Source</label>
            <input
              type="text"
              id="entrantSource"
              name="source"
              className="input-group__text"
              value={newEntrant.source}
              onChange={handleFieldChange}
            />
          </div>
          <div className="input-group">
            <label htmlFor="entrantMeta" className="input-group__label">Media Link (e.g. YouTube, website, etc)</label>
            <input
              type="text"
              id="entrantSource"
              name="meta"
              className="input-group__text"
              value={newEntrant.meta ? newEntrant.meta.link : ''}
              onChange={handleFieldChange}
            />
          </div>
        </div>
        <div className="entrant-modal__actions">
          <button
            onClick={() => onClose()}
            className="button button--secondary button--small"
          >
            Cancel
          </button>
          <button
            onClick={handleSubmitClick}
            className="button button--small"
          >
            OK
          </button>
        </div>
      </div>
    </div>
  );
};

export default EntrantModal;
