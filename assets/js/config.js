
const ENV = {
    DEVELOPMENT: 'development',
    PRODUCTION: 'production'
};

const CURRENT_ENV = ENV.DEVELOPMENT;

const CONFIG = {
    [ENV.DEVELOPMENT]: {
        BASE_URL: 'http://localhost/evently',
    },
    [ENV.PRODUCTION]: {
        BASE_URL: 'https://your-production-domain.com',
    }
};

const ACTIVE_CONFIG = CONFIG[CURRENT_ENV];


