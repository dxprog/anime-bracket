import React, { useState } from 'react';

import AdminEntrantItem from '../AdminEntrantItem';
import EntrantModal from '../EntrantModal';

function encodeFormData(obj) {
  return Object.keys(obj)
    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(obj[key])}`)
    .join('&');
}

const AdminEntrantList = ({ entrants, bracket }) => {
  const [ editingEntrant, setEditingEntrant ] = useState({});
  const [ modalOpen, setModalOpen ] = useState(false);

  const onEntrantEdit = entrant => {
    setEditingEntrant(entrant);
    setModalOpen(true);
  };

  const onEntrantDelete = entrant => {

  };

  const onEntrantCreate = () => {
    setEditingEntrant({});
    setModalOpen(true);
  };

  const onModalClose = () => {
    setModalOpen(false);
  };

  const onModalSubmit = async (newEntrant, cropInfo) => {
    let error = null;
    let forceNewImage = false;

    if (cropInfo) {
      // Regardless of whether information changed, we'll need to
      // update the image path of the new crop
      newEntrant = newEntrant || editingEntrant;

      try {
        const response = await fetch(
          '/me/image/crop/',
          {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: encodeFormData({
              imageFile: newEntrant.image,
              x: cropInfo.x,
              y: cropInfo.y,
              width: cropInfo.width,
              height: cropInfo.height
            })
          }).then(res => res.json());

        if (response.success) {
          newEntrant.image = response.fileName;
          forceNewImage = true;
        } else {
          error = {
            message: response.message
          };
        }
      } catch (err) {
        error = {
          message: 'Server broke cropping the image',
          error: err
        };
      }
    }

    if (newEntrant && !error) {
      const response = await fetch(`/me/process/${bracket.perma}/character`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: encodeFormData({
          action: newEntrant.id ? 'update' : 'create',
          characterId: newEntrant.id || 0,
          name: newEntrant.name || '',
          source: newEntrant.source || '',
          metaLink: typeof newEntrant.meta === 'string' ? newEntrant.meta : '',
          imageFile: (
            newEntrant.image !== editingEntrant.image || forceNewImage
          ) ? newEntrant.image : ''
        })
      }).then(res => res.json());
    }

    setModalOpen(false);
  };

  return (
    <>
      <button
        type="button"
        onClick={onEntrantCreate}
      >
        Create Entrant
      </button>
      <ul className="admin-entrant-list">
        {entrants.map(entrant => (
          <AdminEntrantItem
            onEdit={onEntrantEdit}
            onDelete={onEntrantDelete}
            entrant={entrant}
            key={`entrant${entrant.id}`}
          />
        ))}
      </ul>
      {modalOpen && (
        <EntrantModal
          entrant={editingEntrant}
          onSubmit={onModalSubmit}
          onClose={onModalClose}
        />
      )}
    </>
  );
};

export default AdminEntrantList;
