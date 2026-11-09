// Dezelfde Font Awesome-iconen als de website (fa-solid), maar alleen de SVG's die de app gebruikt, in
// plaats van het hele lettertype. Nieuw icoon nodig? Zet de naam in deze lijst (staat het in public/
// fontawesome/svgs/solid, dan werkt het). De menu-iconen komen van de server (AppMenu), als "fa-naam".
const files = import.meta.glob([
    '../../public/fontawesome/svgs/solid/{calendar-check,magnifying-glass,user,inbox,calendar-days,file-invoice,user-pen,circle-question,envelope,building,clock,chart-line,building-columns,briefcase,gauge,list-check,triangle-exclamation,users,chevron-right,chevron-left,arrow-right-from-bracket,location-dot,phone,check,xmark,rotate-right,globe,headphones,wifi,circle-exclamation,music,euro-sign,receipt,image,circle-check,door-open,newspaper,chevron-down}.svg',
], { query: '?raw', import: 'default', eager: true });

const icons = Object.fromEntries(Object.entries(files).map(([path, svg]) => [
    path.split('/').pop().replace('.svg', ''),
    // Licentieopmerking eruit en aria-hidden erin: het icoon is versiering, de tekst ernaast zegt wat het is.
    svg.replace(/<!--[\s\S]*?-->/, '').replace('<svg ', '<svg aria-hidden="true" '),
]));

export function iconSvg(name) {
    return icons[String(name ?? '').replace(/^fa-/, '')] ?? '';
}
