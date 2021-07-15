import React from 'react';

import './AdminEntrantItem.scss';

const AdminEntrantItem = ({
  bracket,
  entrant,
  onEdit,
  onDelete
}) => {
  return (
    <tr className="admin-entrant-item">
      <td>
        <div className="entrant-lockup">
          <img className="entrant-lockup__image" src={entrant.image} alt={entrant.name} />
          <div className="entrant-lockup__info">
            <span className="entrant-lockup__name" type="text" name="name">{entrant.name}</span>
            <span className="entrant-lockup__source" type="text" name="source">{entrant.source}</span>
          </div>
          {entrant?.meta?.link && (
            <p className="entrant-lockup__meta">
              {entrant.meta.link}
            </p>
          )}
        </div>
      </td>
      <td className="admin-table__actions">
        <button onClick={() => onEdit(entrant)} className="button button--small">Edit</button>
        {typeof onDelete === 'function' && (
          <button onClick={() => onDelete(entrant)} className="button button--small button--critical">Delete</button>
        )}
      </td>
    </tr>
  );
};

export default AdminEntrantItem;
