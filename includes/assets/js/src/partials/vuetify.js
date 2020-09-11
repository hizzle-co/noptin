import Vue from 'vue';
import Vuetify from 'vuetify/lib';
import {VExpansionPanels} from 'vuetify/lib';

Vue.use(Vuetify, {
    components: {
        VExpansionPanels
    }
});

export default new Vuetify({
    icons: {
      iconfont: 'mdiSvg', // 'mdi' || 'mdiSvg' || 'md' || 'fa' || 'fa4' || 'faSvg'
    },
});
