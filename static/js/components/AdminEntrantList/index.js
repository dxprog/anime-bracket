import React, { useState } from 'react';

import AdminEntrantItem from '../AdminEntrantItem';
import EntrantModal from '../EntrantModal';

import './AdminEntrantList.scss';

function encodeFormData(obj) {
  return Object.keys(obj)
    .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(obj[key])}`)
    .join('&');
}

// TODO: this needs to be moved somewhere more generic. brackets probably need models
const BracketStates = {
  NotStarted: 0,
  Nominations: 1,
  Elminations: 2,
  Voting: 3,
  WildCard: 4,
  Final: 5,
  Hidden: 6,
};

const AdminEntrantList = ({ entrants, bracket }) => {
  const canDelete = bracket.state < BracketStates.Voting;

  const [ editingEntrant, setEditingEntrant ] = useState({});
  const [ modalOpen, setModalOpen ] = useState(false);
  const [ entrantsList, setEntrantsList ] = useState(Array.isArray(entrants) ? [ ...entrants ] : []);

  const onEntrantEdit = entrant => {
    setEditingEntrant(entrant);
    setModalOpen(true);
  };

  const onEntrantDelete = async entrant => {
    // hard exit if the bracket is in voting mode
    if (!canDelete) {
      window.alert('Cannot delete entrants after a bracket has passed the elmination round.');
      return;
    }

    const confirmDelete = window.confirm(`Any cast votes for ${entrant.name} will be lost and cannot be recovered. Are you sure you want to proceed?`);
    if (confirmDelete) {
      try {
        const response = await fetch(`/me/process/${bracket.perma}/character`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: encodeFormData({
            action: 'delete',
            characterId: entrant.id,
          })
        }).then(res => res.json());

        if (response.success) {
          setEntrantsList(entrantsList.filter(listEntrant => listEntrant.id !== entrant.id));
        } else {
          throw new Error('Server barfed');
        }
      } catch (err) {
        window.alert(`Encountered an error deleting ${entrant.name}: ${err}`);
      }
    }
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
          metaLink: newEntrant.meta && typeof newEntrant.meta.link === 'string' ? newEntrant.meta.link : '',
          imageFile: (
            newEntrant.image !== editingEntrant.image || forceNewImage
          ) ? newEntrant.image : ''
        })
      }).then(res => res.json());

      if (response?.success) {
        // add new entrants to the beginning of the list
        if (!newEntrant.id) {
          setEntrantsList([ ...entrantsList, newEntrant ]);
        } else {
          // replace the existing entrant with the new one
          setEntrantsList(entrantsList.map(entrant => entrant.id === newEntrant.id ? newEntrant : entrant));
        }
      }
    }

    setModalOpen(false);
  };

  return (
    <>
      {bracket.state < BracketStates.Voting && (
        <button
          type="button"
          onClick={onEntrantCreate}
          className="button button--small"
        >
          Create Entrant
        </button>
      )}
      <table className="admin-table">
        <tbody>
          {entrantsList.map(entrant => (
            <AdminEntrantItem
              onEdit={onEntrantEdit}
              onDelete={canDelete && onEntrantDelete}
              entrant={entrant}
              key={`entrant${entrant.id}`}
            />
          ))}
        </tbody>
      </table>
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
