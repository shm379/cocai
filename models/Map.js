import { Sequelize, DataTypes } from 'sequelize';

// اتصال به دیتابیس — کردنشیال از متغیرهای محیطی خوانده می‌شود (هم‌منبع با .env لاراول).
// اجرا: node --env-file=.env crawler.js
const sequelize = new Sequelize(
  process.env.DB_DATABASE || 'cocai',
  process.env.DB_USERNAME || 'root',
  process.env.DB_PASSWORD || '',
  {
    host: process.env.DB_HOST || '127.0.0.1',
    port: process.env.DB_PORT || 3306,
    dialect: 'mysql',
    logging: false,
  }
);

// تعریف مدل نقشه
const Map = sequelize.define('Map', {
  title: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  image_path: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  map_link: {
    type: DataTypes.STRING,
    allowNull: false,
  },
  town_hall: {
    type: DataTypes.STRING,
    allowNull: false,
  },
}, {
  timestamps: false, // ذخیره زمان‌های ایجاد و بروزرسانی
});

// سینک کردن مدل با دیتابیس
sequelize.sync()
  .then(() => console.log('Database synchronized'))
  .catch(console.error);

export { Map, sequelize };
