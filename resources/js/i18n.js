import { createI18n } from 'vue-i18n';
import ru from './ru.json';
import uz from './uz.json';

const i18n = createI18n({
  legacy: false,
  locale: 'uz', // Default locale
  fallbackLocale: 'ru', // Fallback locale
  messages: {
    ru,
    uz
  }
});

export default i18n;
