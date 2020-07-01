export default function Entrant(data, position) {
  this.position = position;
  if (null != data) {
    this.image = data.image;
    this.name = data.name;
    this.source = data.source;
    this.votes = parseInt(data.votes, 10);
    this.id = data.id;
    this.seed = data.seed;
  } else {
    this.image = 'https://img.animebracket.com/unknown.jpg';
    this.name = '';
    this.source = '';
    this.id = 1;
    this.nobody = true;
  }
}