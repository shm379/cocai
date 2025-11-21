import { Sequelize, DataTypes } from 'sequelize';

// اتصال به دیتابیس
const sequelize = new Sequelize('mysql://root:09391727950@localhost:3306/cocai'); // اطلاعات دیتابیس خود را وارد کنید

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
