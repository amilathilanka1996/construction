import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.sampathproject.constructionmanager',
  appName: 'Construction Manager',
  webDir: 'dist',
  server: {
    androidScheme: 'https',
  },
};

export default config;
