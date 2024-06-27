import DefaultTheme from 'vitepress/theme';
import NostrPHPLayout from './NostrPHPLayout.vue'
import './custom.css';

export default {
    extends: DefaultTheme,
        Layout: NostrPHPLayout,
    }
