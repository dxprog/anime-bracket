export function metaLabel(meta) {
  let retVal = 'More info';

  switch (meta.type) {
    case 'youtube':
      retVal = 'Watch on YouTube';
      break;
    case 'vimeo':
      retVal = 'Watch on Vimeo';
      break;
    case 'dailymotion':
      retVal = 'Watch on Dailymotion';
      break;
    case 'video':
      retVal = 'Watch Video';
      break;
    case 'audio':
      retVal = 'Listen';
      break;
    default:
      if (meta.link) {
        const domain = /^http(s?):\/\/([^\/]+)/ig.exec(meta.link);
        if (domain) {
          retVal = `See more info at ${domain[2]}`;
        }
      }
    }

  return retVal;
}
