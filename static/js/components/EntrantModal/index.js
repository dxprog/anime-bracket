import React, { useState } from 'react';
import ReactCrop from 'react-image-crop';

import 'react-image-crop/lib/ReactCrop.scss';
import './EntrantModal.scss';

const IMAGE_WIDTH = 150;
const IMAGE_HEIGHT = 150;
const ACCEPTED_FORMATS = [
  'image/jpeg',
  'image/gif',
  'image/png'
];

const EmptyImageLockup = ({ onFileUpload }) => (
  <div className="file-upload">
    <input
      className="file-upload__input"
      type="file"
      onChange={onFileUpload}
      id="imageUpload"
    />
    <label htmlFor="imageUpload" className="file-upload__label">
      <img src="/static/images/upload-icon.svg" className="file-upload__icon" />
      <span className="file-upload__cta">Upload Picture</span>
    </label>
  </div>
);

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

  const handleFileUpload = async (evt) => {
    const { files } = evt.target;
    if (!files.length) {
      return;
    }

    const [ file ] = files;
    if (ACCEPTED_FORMATS.indexOf(file.type) === -1) {
      alert('Image must be a JPEG, GIF, or PNG');
      return;
    }

    const formData = new FormData();
    formData.append('upload', file);

    const data = await fetch('/me/image/upload/', {
      method: 'POST',
      headers: { 'X-FileName': file.name },
      body: formData
    }).then(response => response.json());

    if (data.success) {
      setNewEntrant({
        ...newEntrant,
        image: data.fileName
      });
    } else {
      alert(data.message);
    }

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

    onSubmit(isDataDirty && newEntrant, isCropDirty && crop);
  };

  return (
    <div
      className='entrant-modal'
      onClick={handleOverlayClick}
    >
      <div className="entrant-modal__window">
        <div className="entrant-modal__crop">
          {newEntrant.image ? (
            <ReactCrop
              src={newEntrant.image}
              className="entrant-modal__crop-image"
              crop={crop}
              minWidth={IMAGE_WIDTH}
              minHeight={IMAGE_HEIGHT}
              onChange={handleCropChange}
              onImageLoaded={setImage}
            />
          ) : (
            <EmptyImageLockup onFileUpload={handleFileUpload} />
          )}
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
              value={newEntrant.meta ? newEntrant.meta : ''}
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
