-- Food Dash - Module 2: restaurants and menu items (sample data)
-- Import with a UTF-8 client so the emoji are stored correctly, for example:
--   mysql --default-character-set=utf8mb4 -u root < sql/restaurants.sql
-- Run AFTER sql/database.sql. This file only drops and recreates restaurants and menu_items,
-- so users and addresses are not touched. Safe to run again.
USE food_dash;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS restaurants;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE restaurants (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(100) NOT NULL,
  cuisine          VARCHAR(100) NOT NULL,
  area             VARCHAR(60)  NOT NULL,
  address          VARCHAR(255) NOT NULL,
  phone            VARCHAR(20)  NOT NULL,
  opening_hours    VARCHAR(100) NOT NULL,
  rating           DECIMAL(2,1) NOT NULL,
  delivery_minutes SMALLINT UNSIGNED NOT NULL,
  cost_for_two     INT UNSIGNED NOT NULL,
  emoji            VARCHAR(16)  NOT NULL DEFAULT '🍽️',
  image_url        VARCHAR(255) NULL,
  featured         TINYINT(1)   NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE menu_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT UNSIGNED NOT NULL,
  name          VARCHAR(100) NOT NULL,
  category      ENUM('Starters','Main Course','Desserts','Beverages') NOT NULL,
  description   VARCHAR(255) NOT NULL,
  price         DECIMAL(8,2) NOT NULL,
  veg           TINYINT(1)   NOT NULL DEFAULT 1,
  spice         ENUM('None','Mild','Medium','Spicy') NOT NULL DEFAULT 'Mild',
  allergens     VARCHAR(120) NULL,
  emoji         VARCHAR(16)  NOT NULL DEFAULT '🍽️',
  featured      TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT fk_menu_restaurant FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO restaurants (id,name,cuisine,area,address,phone,opening_hours,rating,delivery_minutes,cost_for_two,emoji,featured) VALUES
(1,'Spice Route Kitchen','North Indian, Tandoor','Udupi','14 Car Street, Udupi 576101','0820-200001','11:00 AM - 11:30 PM',4.5,30,600,'🍛',1),
(2,'Udupi Tiffin House','South Indian, Udupi','Udupi','3 Temple Road, Udupi 576101','0820-200002','7:00 AM - 10:30 PM',4.6,25,300,'🥞',1),
(3,'Coastal Tandoor','Seafood, Mangalorean','Manipal','22 Tiger Circle, Manipal 576104','0820-200003','12:00 PM - 11:00 PM',4.2,40,800,'🦐',1),
(4,'Biryani Bazaar','Biryani, Hyderabadi','Mangaluru','9 Hampankatta, Mangaluru 575001','0824-200004','11:30 AM - 11:00 PM',4.3,35,500,'🍚',0),
(5,'Sweet Crumb Bakery','Desserts, Bakery','Udupi','7 Market Lane, Udupi 576101','0820-200005','8:00 AM - 9:00 PM',4.7,20,350,'🍰',1),
(6,'Chai & Chaat Corner','Snacks, Beverages','Manipal','5 Tulip Plaza, Manipal 576104','0820-200006','9:00 AM - 10:00 PM',4.0,20,200,'🥤',0);

INSERT INTO menu_items (restaurant_id,name,category,description,price,veg,spice,allergens,emoji,featured) VALUES
(1,'Paneer Tikka','Starters','Cottage cheese marinated in spiced yogurt and grilled in the tandoor',220,1,'Medium','Dairy','🧀',1),
(1,'Chicken Seekh Kebab','Starters','Minced chicken skewers with herbs and warm spices',260,0,'Spicy','Egg, Dairy','🍢',0),
(1,'Dal Makhani','Main Course','Slow-cooked black lentils finished with butter and cream',240,1,'Mild','Dairy','🍲',0),
(1,'Butter Chicken','Main Course','Tender chicken in a rich tomato and butter gravy',320,0,'Mild','Dairy, Nuts','🍗',1),
(1,'Garlic Naan','Main Course','Leavened bread brushed with garlic and coriander',70,1,'None','Gluten, Dairy','🫓',0),
(1,'Gulab Jamun','Desserts','Soft milk-solid dumplings soaked in rose syrup',120,1,'None','Dairy, Gluten','🍩',0),
(1,'Masala Chaas','Beverages','Spiced buttermilk with roasted cumin',80,1,'Mild','Dairy','🥛',0),
(2,'Masala Dosa','Main Course','Crisp rice crepe with spiced potato filling, served with chutney and sambar',90,1,'Mild','None','🥞',1),
(2,'Idli Vada Combo','Main Course','Steamed rice cakes and crisp lentil fritters with sambar',80,1,'Mild','Gluten','🍘',0),
(2,'Rava Upma','Starters','Semolina cooked with mustard seeds, curry leaves and vegetables',60,1,'Mild','Gluten','🥣',0),
(2,'Mini Tiffin Thali','Main Course','Rice, sambar, rasam, two curries, papad and payasam',180,1,'Medium','Dairy','🍱',1),
(2,'Filter Coffee','Beverages','Strong South Indian coffee brewed with chicory and milk',40,1,'None','Dairy','☕',0),
(3,'Prawn Ghee Roast','Starters','Prawns roasted in spiced ghee with Mangalorean masala',340,0,'Spicy','Shellfish, Dairy','🦐',1),
(3,'Fish Tikka','Starters','Boneless fish marinated with ajwain and grilled',300,0,'Medium','Fish, Dairy','🐟',0),
(3,'Mangalorean Fish Curry','Main Course','Fish in a tangy coconut and kokum curry, served with rice',280,0,'Spicy','Fish','🍛',0),
(3,'Neer Dosa','Main Course','Thin, soft rice crepes served with a coconut chutney',110,1,'Mild','None','🥞',0),
(3,'Tender Coconut Pudding','Desserts','Chilled coconut custard with cardamom',150,1,'None','Dairy','🥥',0),
(4,'Hyderabadi Chicken Biryani','Main Course','Basmati rice layered with chicken and saffron, cooked dum-style',320,0,'Medium','Dairy, Nuts','🍚',1),
(4,'Veg Biryani','Main Course','Fragrant basmati with seasonal vegetables and fried onions',260,1,'Mild','Dairy','🥗',0),
(4,'Mirchi Ka Salan','Starters','Green chillies in a peanut and sesame gravy',120,1,'Spicy','Nuts','🌶️',0),
(4,'Double Ka Meetha','Desserts','Bread pudding soaked in saffron milk and topped with nuts',140,1,'None','Dairy, Gluten, Nuts','🍮',0),
(5,'Chocolate Truffle Cake','Desserts','Dark chocolate sponge layered with truffle cream',180,1,'None','Egg, Dairy, Gluten','🎂',1),
(5,'Blueberry Cheesecake','Desserts','Baked cheesecake on a biscuit base with blueberry compote',210,1,'None','Egg, Dairy, Gluten','🍰',1),
(5,'Cinnamon Roll','Desserts','Soft roll with cinnamon sugar and cream cheese glaze',110,1,'None','Gluten, Dairy','🥐',0),
(5,'Cold Coffee','Beverages','Chilled espresso blended with milk and ice',140,1,'None','Dairy','🧋',0),
(6,'Pani Puri','Starters','Crisp shells filled with spiced water, potato and chickpeas',60,1,'Spicy','Gluten','🫧',1),
(6,'Samosa Chaat','Starters','Crushed samosa topped with yogurt, chutneys and sev',90,1,'Medium','Gluten, Dairy','🥟',0),
(6,'Masala Chai','Beverages','Milk tea simmered with ginger and cardamom',30,1,'Mild','Dairy','🍵',1),
(6,'Mango Lassi','Beverages','Thick yogurt drink blended with Alphonso mango',110,1,'None','Dairy','🥭',0);
