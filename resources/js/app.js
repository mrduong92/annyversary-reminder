import './bootstrap';
import Alpine from 'alpinejs';
import 'flowbite';
import * as f3 from 'family-chart';

window.Alpine = Alpine;
window.f3 = f3;   // expose globally để dùng trong blade scripts
Alpine.start();
