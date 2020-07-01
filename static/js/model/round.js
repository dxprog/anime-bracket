import Entrant from './entrant';

export default function Round(data) {
  this.tier = parseInt(data.tier, 10);
  this.group = parseInt(data.group, 10);
  this.order = parseInt(data.order, 10);
  this.final = !!data.final;
  if(data.character1) {
    this.entrants = 1;
    this.entrant1 = new Entrant(data.character1, 'top');
    this.id = data.id;

    // Character ID is reserved for "nobody", used in wildcard and eliminations
    if (null !== data.character2 && data.character2.id !== 1) {
      this.entrant2 = new Entrant(data.character2, 'bottom');
      this.entrants = 2;
    }
  } else {
    // If we got both back as nulls, assume unfinished future rounds
    this.entrants = 2;
    this.entrant1 = new Entrant(null, 'top');
    this.entrant2 = new Entrant(null, 'bottom');
  }
}