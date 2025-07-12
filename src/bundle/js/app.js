// PivotPHP Application JavaScript
class PivotApp {
    constructor() {
        this.apiBase = '/api';
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadInitialData();
    }
    
    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action]')) {
                const action = e.target.dataset.action;
                this.handleAction(action, e.target);
            }
        });
    }
    
    async loadInitialData() {
        try {
            const health = await this.api('/health');
            console.log('API Health:', health);
        } catch (error) {
            console.error('Failed to load initial data:', error);
        }
    }
    
    async api(endpoint, options = {}) {
        const url = this.apiBase + '/' + endpoint.replace(/^\//, '');
        return PivotPHP.ajax(url, options);
    }
    
    handleAction(action, element) {
        switch (action) {
            case 'reload':
                location.reload();
                break;
            case 'api-test':
                this.testApi();
                break;
            default:
                console.log('Unknown action:', action);
        }
    }
    
    async testApi() {
        try {
            const endpoints = ['/health', '/version', '/config'];
            
            for (const endpoint of endpoints) {
                const result = await this.api(endpoint);
                console.log(`${endpoint}:`, result);
            }
        } catch (error) {
            console.error('API test failed:', error);
        }
    }
}

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    window.app = new PivotApp();
});