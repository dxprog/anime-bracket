import React from 'react';

import './AdminEntrantItem.scss';

const AdminEntrantItem = ({
  bracket,
  entrant,
  onEdit,
  onDelete
}) => {
  return (
    <li className="admin-entrant-item">
      <div className="entrant-lockup">
        <img className="entrant-lockup__image" src={entrant.image} alt={entrant.name} />
        <div className="entrant-lockup__info">
          <span className="entrant-lockup__name" type="text" name="name" value={entrant.name} />
          <span className="entrant-lockup__source" type="text" name="source" value={entrant.source} />
        </div>
      </div>
      <div className="admin-entrant-item__actions">
        <button onClick={() => onEdit(entrant)}>Edit</button>
      </div>
    </li>
  );
};

export default AdminEntrantItem;
