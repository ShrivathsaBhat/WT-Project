CREATE DATABASE IF NOT EXISTS fooddash CHARACTER SET utf8mb4;
USE fooddash;
DROP TABLE IF EXISTS dishes; DROP TABLE IF EXISTS categories; DROP TABLE IF EXISTS restaurants;
CREATE TABLE restaurants(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,cuisine VARCHAR(150) NOT NULL,rating DECIMAL(2,1) NOT NULL,delivery_min TINYINT NOT NULL,delivery_max TINYINT NOT NULL,address VARCHAR(255) NOT NULL,phone VARCHAR(30) NOT NULL,opening_hours VARCHAR(100) NOT NULL,image VARCHAR(150) DEFAULT NULL,INDEX(name),INDEX(rating)) ENGINE=InnoDB;
CREATE TABLE categories(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(50) NOT NULL UNIQUE) ENGINE=InnoDB;
CREATE TABLE dishes(id INT AUTO_INCREMENT PRIMARY KEY,restaurant_id INT NOT NULL,category_id INT NOT NULL,name VARCHAR(100) NOT NULL,description TEXT NOT NULL,price DECIMAL(7,2) NOT NULL,rating DECIMAL(2,1) NOT NULL,calories SMALLINT NOT NULL,diet ENUM('veg','nonveg','vegan') NOT NULL,spice TINYINT NOT NULL COMMENT '1 Mild,2 Medium,3 Spicy',contains_gluten TINYINT(1) NOT NULL DEFAULT 0,contains_dairy TINYINT(1) NOT NULL DEFAULT 0,contains_nuts TINYINT(1) NOT NULL DEFAULT 0,is_special TINYINT(1) NOT NULL DEFAULT 0,popularity INT NOT NULL DEFAULT 0,image VARCHAR(150) DEFAULT NULL,
FOREIGN KEY(restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,FOREIGN KEY(category_id) REFERENCES categories(id),INDEX(price),INDEX(rating),INDEX(is_special),FULLTEXT(name,description)) ENGINE=InnoDB;
INSERT INTO restaurants(name,cuisine,rating,delivery_min,delivery_max,address,phone,opening_hours) VALUES
('Pai Tiffins','South Indian · Dosa · Tiffin',4.6,15,25,'Sample address, Udupi, Karnataka','+91 00000 00001','7:00 AM – 9:30 PM'),
('Krishna Kuteera','Pure Veg · South Indian · Meals',4.2,15,30,'Sample address, Udupi, Karnataka','+91 00000 00002','7:30 AM – 10:00 PM'),
('Shree Shanthi Sagar','Vegetarian · South Indian · Thali',4.0,15,30,'Sample address, Udupi, Karnataka','+91 00000 00003','8:00 AM – 10:00 PM'),
('Anupam Restaurant','South Indian · Mangalorean · Neer Dosa',4.3,15,25,'Sample address, Udupi, Karnataka','+91 00000 00004','8:00 AM – 9:30 PM'),
('Dollops','Indian · Indo-Chinese · Student Favourite',4.2,20,35,'Sample address, Udupi, Karnataka','+91 00000 00005','11:00 AM – 11:00 PM'),
('Shalas','Mangalorean · Coastal · Seafood',4.6,25,40,'Sample address, Udupi, Karnataka','+91 00000 00006','12:00 PM – 11:00 PM'),
('Hotel Manipal Donne Biryani','Biryani · Non-Veg · Karnataka',4.5,20,35,'Sample address, Manipal, Karnataka','+91 00000 00007','11:00 AM – 10:30 PM'),
('Coastal Spice Kitchen','Seafood · Mangalorean',4.1,25,40,'Sample address, Udupi, Karnataka','+91 00000 00008','11:30 AM – 10:30 PM'),
('Sweet Treats Parlour','Desserts · Ice Cream · Beverages',4.4,10,20,'Sample address, Udupi, Karnataka','+91 00000 00009','10:00 AM – 11:00 PM'),
('Udupi Meals House','Pure Veg · Meals · Thali',4.0,20,30,'Sample address, Udupi, Karnataka','+91 00000 00010','11:00 AM – 3:30 PM');
INSERT INTO categories(name) VALUES('Starters'),('Main Course'),('Desserts'),('Beverages'),('Tiffins');
INSERT INTO dishes(restaurant_id,category_id,name,description,price,rating,calories,diet,spice,contains_gluten,contains_dairy,contains_nuts,is_special,popularity,image) VALUES
(1,5,'Masala Dosa','Crispy South Indian dosa filled with spiced potato masala, served with coconut chutney and sambar.',90,4.7,320,'veg',2,0,1,0,1,98,'images/masala-dosa.jpg'),
(7,2,'Chicken Donne Biryani','Fragrant seeraga samba rice cooked with tender chicken and Karnataka-style spices, served in a leaf bowl.',180,4.5,610,'nonveg',3,0,1,0,1,95,'images/donne-biryani.jpg'),
(1,5,'Mangalore Buns','Soft, lightly sweet banana puris served with coconut chutney and sambar.',70,4.6,280,'veg',1,1,0,0,1,90,'images/mangalore-buns.jpg'),
(5,1,'Gobi Manchurian','Crispy cauliflower tossed in a tangy Indo-Chinese sauce.',120,4.2,340,'veg',2,1,0,0,0,70,NULL),
(6,1,'Fish Tawa Fry','Fresh catch coated in Mangalorean masala and pan-fried on a tawa.',220,4.6,360,'nonveg',3,0,0,0,0,80,NULL),
(4,2,'Neer Dosa with Chicken Curry','Lacy rice crepes served with a coconut-based Mangalorean chicken curry.',160,4.3,450,'nonveg',2,0,0,0,0,75,NULL),
(3,2,'Veg Thali','Rice, sambar, rasam, two vegetable curries, chapati, pickle, papad and payasa.',130,4.0,720,'veg',1,1,1,1,0,60,NULL),
(2,2,'Karnataka Meals','Unlimited rice, sambar, rasam, palya, kosambari, curd and payasa on a banana leaf.',110,4.2,680,'veg',1,0,1,0,0,65,NULL),
(9,3,'Gadbad Ice Cream','Layered ice cream sundae with fruit, jelly and dry fruits.',110,4.4,420,'veg',1,0,1,1,0,72,NULL),
(1,4,'Filter Coffee','Strong South Indian filter coffee with frothy hot milk.',50,4.5,90,'veg',1,0,1,0,0,85,NULL),
(1,5,'Idli Vada','Two steamed idlis and a crisp medu vada with sambar and chutney.',60,4.4,260,'vegan',1,0,0,0,0,78,NULL),
(4,5,'Set Dosa','Three soft, spongy dosas served with vegetable sagu and chutney.',80,4.3,300,'vegan',1,0,0,0,0,68,NULL);
