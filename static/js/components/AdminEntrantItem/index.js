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
        </div>
      </td>
      <td className="admin-table__actions">
        <button onClick={() => onEdit(entrant)} className="button button--small">Edit</button>
      </td>
    </tr>
  );
};

export default AdminEntrantItem;
