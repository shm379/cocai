// models/Topic.js
import { Model, DataTypes } from 'sequelize';
import sequelize from '../database.js'; // مسیر فایل database.js را بررسی کنید

class Topic extends Model {}

Topic.init({
    id: {
        type: DataTypes.INTEGER,
        autoIncrement: true,
        primaryKey: true,
    },
    name: {
        type: DataTypes.STRING,
        allowNull: false,
        unique: true, // جلوگیری از تاپیک‌های تکراری
    }
}, {
    sequelize,
    modelName: 'Topic',
    tableName: 'Topics',
    timestamps: false, // فعال‌سازی timestamps در صورت نیاز
});

export { Topic };
