--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.4

-- Started on 2025-05-22 02:49:34

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 228 (class 1259 OID 16700)
-- Name: completed_sales; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.completed_sales (
    sale_id integer NOT NULL,
    source character varying(255) DEFAULT 'ebay'::character varying NOT NULL,
    source_listing_id character varying(255),
    sale_date timestamp with time zone NOT NULL,
    sale_price numeric(10,2) NOT NULL,
    currency character varying(255) DEFAULT 'USD'::character varying NOT NULL,
    condition character varying(255) DEFAULT 'good'::character varying NOT NULL,
    is_complete_set boolean DEFAULT false NOT NULL,
    imported_at timestamp with time zone NOT NULL
);


ALTER TABLE public.completed_sales OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16699)
-- Name: completed_sales_sale_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.completed_sales_sale_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.completed_sales_sale_id_seq OWNER TO postgres;

--
-- TOC entry 5123 (class 0 OID 0)
-- Dependencies: 227
-- Name: completed_sales_sale_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.completed_sales_sale_id_seq OWNED BY public.completed_sales.sale_id;


--
-- TOC entry 232 (class 1259 OID 18572)
-- Name: inventory; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.inventory (
    inventory_id integer NOT NULL,
    volume_id integer NOT NULL,
    condition character varying(255) DEFAULT 'good'::character varying NOT NULL,
    purchase_price numeric(10,2) NOT NULL,
    purchase_date timestamp with time zone NOT NULL,
    notes text,
    status character varying(255) DEFAULT 'in_stock'::character varying NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.inventory OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 18571)
-- Name: inventory_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.inventory_inventory_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inventory_inventory_id_seq OWNER TO postgres;

--
-- TOC entry 5124 (class 0 OID 0)
-- Dependencies: 231
-- Name: inventory_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.inventory_inventory_id_seq OWNED BY public.inventory.inventory_id;


--
-- TOC entry 236 (class 1259 OID 18598)
-- Name: listing_inventory; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.listing_inventory (
    listing_inventory_id integer NOT NULL,
    listing_id integer NOT NULL,
    inventory_id integer NOT NULL
);


ALTER TABLE public.listing_inventory OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 18597)
-- Name: listing_inventory_listing_inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.listing_inventory_listing_inventory_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.listing_inventory_listing_inventory_id_seq OWNER TO postgres;

--
-- TOC entry 5125 (class 0 OID 0)
-- Dependencies: 235
-- Name: listing_inventory_listing_inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.listing_inventory_listing_inventory_id_seq OWNED BY public.listing_inventory.listing_inventory_id;


--
-- TOC entry 234 (class 1259 OID 18588)
-- Name: listings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.listings (
    listing_id integer NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    price numeric(10,2) NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.listings OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 18587)
-- Name: listings_listing_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.listings_listing_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.listings_listing_id_seq OWNER TO postgres;

--
-- TOC entry 5126 (class 0 OID 0)
-- Dependencies: 233
-- Name: listings_listing_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.listings_listing_id_seq OWNED BY public.listings.listing_id;


--
-- TOC entry 226 (class 1259 OID 16684)
-- Name: price_history; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.price_history (
    price_history_id integer NOT NULL,
    series_id integer NOT NULL,
    start_volume integer NOT NULL,
    end_volume integer NOT NULL,
    avg_price_per_volume numeric(10,2) NOT NULL,
    set_price numeric(10,2) NOT NULL,
    is_complete_set boolean DEFAULT false NOT NULL,
    condition character varying(255) DEFAULT 'good'::character varying NOT NULL,
    source character varying(255),
    record_date timestamp with time zone NOT NULL
);


ALTER TABLE public.price_history OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16683)
-- Name: price_history_price_history_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.price_history_price_history_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.price_history_price_history_id_seq OWNER TO postgres;

--
-- TOC entry 5127 (class 0 OID 0)
-- Dependencies: 225
-- Name: price_history_price_history_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.price_history_price_history_id_seq OWNED BY public.price_history.price_history_id;


--
-- TOC entry 240 (class 1259 OID 28352)
-- Name: product_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.product_images (
    id integer NOT NULL,
    product_id integer NOT NULL,
    image_url character varying(255) NOT NULL,
    is_primary boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.product_images OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 28351)
-- Name: product_images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.product_images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.product_images_id_seq OWNER TO postgres;

--
-- TOC entry 5128 (class 0 OID 0)
-- Dependencies: 239
-- Name: product_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.product_images_id_seq OWNED BY public.product_images.id;


--
-- TOC entry 238 (class 1259 OID 28341)
-- Name: products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.products (
    id integer NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    price numeric(10,2) NOT NULL,
    condition character varying(50) NOT NULL,
    ebay_item_id character varying(50),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    shipping character varying(100)
);


ALTER TABLE public.products OWNER TO postgres;

--
-- TOC entry 237 (class 1259 OID 28340)
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO postgres;

--
-- TOC entry 5129 (class 0 OID 0)
-- Dependencies: 237
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- TOC entry 230 (class 1259 OID 16713)
-- Name: sale_volumes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sale_volumes (
    sale_volume_id integer NOT NULL,
    sale_id integer NOT NULL,
    volume_id integer NOT NULL
);


ALTER TABLE public.sale_volumes OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16712)
-- Name: sale_volumes_sale_volume_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sale_volumes_sale_volume_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sale_volumes_sale_volume_id_seq OWNER TO postgres;

--
-- TOC entry 5130 (class 0 OID 0)
-- Dependencies: 229
-- Name: sale_volumes_sale_volume_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sale_volumes_sale_volume_id_seq OWNED BY public.sale_volumes.sale_volume_id;


--
-- TOC entry 244 (class 1259 OID 28383)
-- Name: sell_submission_notes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sell_submission_notes (
    id integer NOT NULL,
    submission_id integer,
    note text,
    status character varying(32),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.sell_submission_notes OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 28382)
-- Name: sell_submission_notes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sell_submission_notes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sell_submission_notes_id_seq OWNER TO postgres;

--
-- TOC entry 5131 (class 0 OID 0)
-- Dependencies: 243
-- Name: sell_submission_notes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sell_submission_notes_id_seq OWNED BY public.sell_submission_notes.id;


--
-- TOC entry 242 (class 1259 OID 28371)
-- Name: sell_submissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sell_submissions (
    id integer NOT NULL,
    full_name character varying(255),
    email character varying(255),
    phone character varying(50),
    num_items integer,
    overall_condition character varying(50),
    item_details text,
    photo_paths text,
    submitted_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    status character varying(32) DEFAULT 'Incomplete'::character varying,
    note text,
    status_updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.sell_submissions OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 28370)
-- Name: sell_submissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sell_submissions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sell_submissions_id_seq OWNER TO postgres;

--
-- TOC entry 5132 (class 0 OID 0)
-- Dependencies: 241
-- Name: sell_submissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sell_submissions_id_seq OWNED BY public.sell_submissions.id;


--
-- TOC entry 222 (class 1259 OID 16390)
-- Name: series; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.series (
    series_id integer NOT NULL,
    name character varying(255) NOT NULL,
    publisher character varying(255),
    total_volumes integer,
    status character varying(255) DEFAULT 'ongoing'::character varying,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.series OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16389)
-- Name: series_series_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.series_series_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.series_series_id_seq OWNER TO postgres;

--
-- TOC entry 5133 (class 0 OID 0)
-- Dependencies: 221
-- Name: series_series_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.series_series_id_seq OWNED BY public.series.series_id;


--
-- TOC entry 224 (class 1259 OID 16446)
-- Name: volumes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.volumes (
    volume_id integer NOT NULL,
    volume_number integer NOT NULL,
    isbn character varying(255),
    retail_price numeric(10,2),
    release_date timestamp with time zone,
    cover_image character varying(255),
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    series_id integer
);


ALTER TABLE public.volumes OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16445)
-- Name: volumes_volume_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.volumes_volume_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.volumes_volume_id_seq OWNER TO postgres;

--
-- TOC entry 5134 (class 0 OID 0)
-- Dependencies: 223
-- Name: volumes_volume_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.volumes_volume_id_seq OWNED BY public.volumes.volume_id;


--
-- TOC entry 4760 (class 2604 OID 16703)
-- Name: completed_sales sale_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.completed_sales ALTER COLUMN sale_id SET DEFAULT nextval('public.completed_sales_sale_id_seq'::regclass);


--
-- TOC entry 4766 (class 2604 OID 18575)
-- Name: inventory inventory_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory ALTER COLUMN inventory_id SET DEFAULT nextval('public.inventory_inventory_id_seq'::regclass);


--
-- TOC entry 4771 (class 2604 OID 18601)
-- Name: listing_inventory listing_inventory_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listing_inventory ALTER COLUMN listing_inventory_id SET DEFAULT nextval('public.listing_inventory_listing_inventory_id_seq'::regclass);


--
-- TOC entry 4769 (class 2604 OID 18591)
-- Name: listings listing_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listings ALTER COLUMN listing_id SET DEFAULT nextval('public.listings_listing_id_seq'::regclass);


--
-- TOC entry 4757 (class 2604 OID 16687)
-- Name: price_history price_history_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.price_history ALTER COLUMN price_history_id SET DEFAULT nextval('public.price_history_price_history_id_seq'::regclass);


--
-- TOC entry 4775 (class 2604 OID 28355)
-- Name: product_images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images ALTER COLUMN id SET DEFAULT nextval('public.product_images_id_seq'::regclass);


--
-- TOC entry 4772 (class 2604 OID 28344)
-- Name: products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- TOC entry 4765 (class 2604 OID 16716)
-- Name: sale_volumes sale_volume_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sale_volumes ALTER COLUMN sale_volume_id SET DEFAULT nextval('public.sale_volumes_sale_volume_id_seq'::regclass);


--
-- TOC entry 4782 (class 2604 OID 28386)
-- Name: sell_submission_notes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sell_submission_notes ALTER COLUMN id SET DEFAULT nextval('public.sell_submission_notes_id_seq'::regclass);


--
-- TOC entry 4778 (class 2604 OID 28374)
-- Name: sell_submissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sell_submissions ALTER COLUMN id SET DEFAULT nextval('public.sell_submissions_id_seq'::regclass);


--
-- TOC entry 4754 (class 2604 OID 16393)
-- Name: series series_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series ALTER COLUMN series_id SET DEFAULT nextval('public.series_series_id_seq'::regclass);


--
-- TOC entry 4756 (class 2604 OID 16449)
-- Name: volumes volume_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.volumes ALTER COLUMN volume_id SET DEFAULT nextval('public.volumes_volume_id_seq'::regclass);


--
-- TOC entry 5101 (class 0 OID 16700)
-- Dependencies: 228
-- Data for Name: completed_sales; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.completed_sales (sale_id, source, source_listing_id, sale_date, sale_price, currency, condition, is_complete_set, imported_at) FROM stdin;
1	ebay	ebay123456	2025-03-24 21:09:46.434-04	45.99	USD	good	t	2025-04-03 21:09:46.436-04
2	ebay	ebay123457	2025-03-19 21:09:46.434-04	52.99	USD	very_good	t	2025-04-03 21:09:46.448-04
3	ebay	ebay123458	2025-03-14 21:09:46.434-04	26.99	USD	good	f	2025-04-03 21:09:46.457-04
4	ebay	ebay123459	2025-03-09 21:09:46.434-04	8.99	USD	acceptable	f	2025-04-03 21:09:46.466-04
5	ebay	ebay123460	2025-02-17 20:09:46.434-05	42.99	USD	good	t	2025-04-03 21:09:46.469-04
6	ebay	325823733790	2025-03-14 00:00:00-04	12.00	USD	good	f	2025-04-07 20:33:37.804-04
7	ebay	405402039878	2025-03-08 00:00:00-05	77.70	USD	good	f	2025-04-07 20:33:37.837-04
8	ebay	167257174029	2025-04-07 00:00:00-04	20.99	USD	good	f	2025-04-07 20:33:37.843-04
9	ebay	364057426731	2025-04-04 00:00:00-04	115.20	USD	good	f	2025-04-07 20:33:37.845-04
10	ebay	167403921090	2025-03-31 00:00:00-04	39.44	USD	good	f	2025-04-07 20:33:37.848-04
11	ebay	176633469137	2025-03-31 00:00:00-04	30.00	USD	good	f	2025-04-07 20:33:37.851-04
12	ebay	356665037022	2025-03-30 00:00:00-04	33.99	USD	good	f	2025-04-07 20:33:37.853-04
13	ebay	135637551212	2025-03-26 00:00:00-04	72.00	USD	good	f	2025-04-07 20:33:37.856-04
14	ebay	306131235987	2025-03-26 00:00:00-04	110.00	USD	good	f	2025-04-07 20:33:37.859-04
15	ebay	167374260426	2025-03-25 00:00:00-04	110.00	USD	good	f	2025-04-07 20:33:37.862-04
16	ebay	126913889215	2025-03-25 00:00:00-04	189.99	USD	good	f	2025-04-07 20:33:37.864-04
17	ebay	286434045944	2025-03-24 00:00:00-04	50.00	USD	good	f	2025-04-07 20:33:37.867-04
18	ebay	187075125413	2025-03-24 00:00:00-04	90.00	USD	good	f	2025-04-07 20:33:37.869-04
19	ebay	187048764448	2025-03-24 00:00:00-04	105.00	USD	good	f	2025-04-07 20:33:37.874-04
20	ebay	176485957545	2024-09-05 00:00:00-04	19.99	USD	good	f	2025-04-07 20:33:37.876-04
21	ebay	296614371663	2025-03-22 00:00:00-04	175.50	USD	good	f	2025-04-07 20:33:37.881-04
22	ebay	156739460320	2025-03-22 00:00:00-04	55.10	USD	good	f	2025-04-07 20:33:37.883-04
23	ebay	376061310722	2025-03-21 00:00:00-04	71.99	USD	good	f	2025-04-07 20:33:37.888-04
24	ebay	404993111211	2025-03-20 00:00:00-04	6.00	USD	good	f	2025-04-07 20:33:37.893-04
25	ebay	155122103705	2025-03-19 00:00:00-04	31.99	USD	good	f	2025-04-07 20:33:37.895-04
26	ebay	286320673132	2025-03-19 00:00:00-04	5.99	USD	good	f	2025-04-07 20:33:37.897-04
27	ebay	276938541047	2025-03-16 00:00:00-04	61.00	USD	good	f	2025-04-07 20:33:37.899-04
28	ebay	167374257881	2025-03-14 00:00:00-04	110.00	USD	good	f	2025-04-07 20:33:37.902-04
29	ebay	167374248688	2025-03-13 00:00:00-04	110.00	USD	good	f	2025-04-07 20:33:37.905-04
30	ebay	267027579821	2025-03-13 00:00:00-04	19.00	USD	good	f	2025-04-07 20:33:37.91-04
31	ebay	235996994433	2025-03-13 00:00:00-04	115.00	USD	good	f	2025-04-07 20:33:37.912-04
32	ebay	356627212967	2025-03-13 00:00:00-04	39.99	USD	good	f	2025-04-07 20:33:37.915-04
33	ebay	235843096279	2025-03-13 00:00:00-04	129.00	USD	good	f	2025-04-07 20:33:37.918-04
34	ebay	167366407892	2025-03-12 00:00:00-04	24.99	USD	good	f	2025-04-07 20:33:37.92-04
35	ebay	135609887617	2025-03-10 00:00:00-04	5.90	USD	good	f	2025-04-07 20:33:37.922-04
36	ebay	314133169580	2025-03-07 00:00:00-05	74.99	USD	good	f	2025-04-07 20:33:37.924-04
37	ebay	396284183410	2025-03-07 00:00:00-05	139.95	USD	good	f	2025-04-07 20:33:37.927-04
38	ebay	135426336579	2025-03-05 00:00:00-05	499.99	USD	good	f	2025-04-07 20:33:37.929-04
39	ebay	176714781558	2025-03-03 00:00:00-05	18.00	USD	good	f	2025-04-07 20:33:37.931-04
40	ebay	167349908922	2025-03-03 00:00:00-05	39.44	USD	good	f	2025-04-07 20:33:37.935-04
41	ebay	126794139189	2025-03-03 00:00:00-05	20.00	USD	good	f	2025-04-07 20:33:37.937-04
42	ebay	225020345816	2025-03-01 00:00:00-05	104.99	USD	good	f	2025-04-07 20:33:37.939-04
43	ebay	167336160203	2025-02-28 00:00:00-05	30.00	USD	good	f	2025-04-07 20:33:37.941-04
44	ebay	116486559756	2025-02-26 00:00:00-05	24.99	USD	good	f	2025-04-07 20:33:37.943-04
45	ebay	356305082580	2025-02-25 00:00:00-05	199.00	USD	good	f	2025-04-07 20:33:37.945-04
46	ebay	135286872382	2025-02-24 00:00:00-05	12.88	USD	good	f	2025-04-07 20:33:37.949-04
47	ebay	326422841195	2025-02-22 00:00:00-05	380.00	USD	good	f	2025-04-07 20:33:37.951-04
48	ebay	396214658901	2025-02-22 00:00:00-05	150.00	USD	good	f	2025-04-07 20:33:37.954-04
49	ebay	ebay123456	2025-03-28 22:27:23.691-04	45.99	USD	good	t	2025-04-07 22:27:23.692-04
50	ebay	ebay123457	2025-03-23 22:27:23.691-04	52.99	USD	very_good	t	2025-04-07 22:27:23.714-04
51	ebay	ebay123458	2025-03-18 22:27:23.691-04	26.99	USD	good	f	2025-04-07 22:27:23.724-04
52	ebay	ebay123459	2025-03-13 22:27:23.691-04	8.99	USD	acceptable	f	2025-04-07 22:27:23.736-04
53	ebay	ebay123460	2025-02-21 21:27:23.691-05	42.99	USD	good	t	2025-04-07 22:27:23.739-04
54	ebay	ebay123456	2025-03-28 22:29:00.349-04	45.99	USD	good	t	2025-04-07 22:29:00.35-04
55	ebay	ebay123457	2025-03-23 22:29:00.349-04	52.99	USD	very_good	t	2025-04-07 22:29:00.367-04
56	ebay	ebay123458	2025-03-18 22:29:00.349-04	26.99	USD	good	f	2025-04-07 22:29:00.379-04
57	ebay	ebay123459	2025-03-13 22:29:00.349-04	8.99	USD	acceptable	f	2025-04-07 22:29:00.388-04
58	ebay	ebay123460	2025-02-21 21:29:00.349-05	42.99	USD	good	t	2025-04-07 22:29:00.392-04
\.


--
-- TOC entry 5105 (class 0 OID 18572)
-- Dependencies: 232
-- Data for Name: inventory; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.inventory (inventory_id, volume_id, condition, purchase_price, purchase_date, notes, status, created_at, updated_at) FROM stdin;
1	1	good	7.99	2025-04-03 21:17:55.353-04	From local comic shop	listed	2025-04-03 21:17:55.354-04	2025-04-03 21:18:03.26-04
\.


--
-- TOC entry 5109 (class 0 OID 18598)
-- Dependencies: 236
-- Data for Name: listing_inventory; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.listing_inventory (listing_inventory_id, listing_id, inventory_id) FROM stdin;
1	1	1
\.


--
-- TOC entry 5107 (class 0 OID 18588)
-- Dependencies: 234
-- Data for Name: listings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.listings (listing_id, title, description, price, status, created_at, updated_at) FROM stdin;
1	Naruto Vol 1 - Good Condition	First volume of Naruto in good condition	12.99	active	2025-04-03 21:18:03.255-04	2025-04-03 21:18:03.255-04
\.


--
-- TOC entry 5099 (class 0 OID 16684)
-- Dependencies: 226
-- Data for Name: price_history; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.price_history (price_history_id, series_id, start_volume, end_volume, avg_price_per_volume, set_price, is_complete_set, condition, source, record_date) FROM stdin;
1	1	1	5	5.44	29.40	f	good	algorithm	2025-04-03 21:10:18.743-04
2	1	1	3	5.44	16.33	f	good	algorithm	2025-04-03 21:10:34.785-04
3	1	1	10	5.44	58.80	f	good	algorithm	2025-04-07 20:43:33.737-04
4	1	1	72	5.44	450.81	t	good	algorithm	2025-04-07 20:54:09.398-04
\.


--
-- TOC entry 5113 (class 0 OID 28352)
-- Dependencies: 240
-- Data for Name: product_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.product_images (id, product_id, image_url, is_primary, created_at) FROM stdin;
\.


--
-- TOC entry 5111 (class 0 OID 28341)
-- Dependencies: 238
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.products (id, title, description, price, condition, ebay_item_id, created_at, updated_at, shipping) FROM stdin;
169	Pop! Animation Dragonball Z #121 Super Saiyan God Super Saiyan Goku Hot No Box		2.70	Used	\N	2025-05-22 00:55:31.247782	2025-05-22 00:55:31.247782	\N
170	Case Closed Volume / Vol. 2, 3, 5, 6, 7, 10, 13, 14 English Manga Viz 2005 Conan		40.50	Very Good	\N	2025-05-22 00:55:31.248959	2025-05-22 00:55:31.248959	\N
171	Remina Hardcover English Manga Junji Ito Viz Signature Horror		8.10	Like New	\N	2025-05-22 00:55:31.24919	2025-05-22 00:55:31.24919	\N
172	The Seven Deadly Sins, Vol. 1 & 2 by Nakaba Suzuki (Kodansha, English Manga) Viz		8.10	Very Good	\N	2025-05-22 00:55:31.249419	2025-05-22 00:55:31.249419	\N
173	Death Note, Vol. 4, 5, 7 - Paperback By Ohba, Tsugumi - Viz Media Shonen Jump		9.90	Very Good	\N	2025-05-22 00:55:31.249635	2025-05-22 00:55:31.249635	\N
174	Kiss Of The Rose Princess Vol. 1 + 4 (Yen Press, 2008) Aya Shouoto English Manga		10.80	Very Good	\N	2025-05-22 00:55:31.249855	2025-05-22 00:55:31.249855	\N
175	Our Dreams at Dusk Volumes 1-2 English Manga Books New Softcover Seven Seas		10.80	Like New	\N	2025-05-22 00:55:31.250073	2025-05-22 00:55:31.250073	\N
176	Komi Can’t Communicate Manga Volumes 1, 4, 8, 17, 23, 24 English Tomohito Oda		23.40	Very Good	\N	2025-05-22 00:55:31.250285	2025-05-22 00:55:31.250285	\N
177	Dragon Ball Assorted Vol 1, Z 23, 25 & Super 12, 16, 17English Manga Volumes		22.50	Like New	\N	2025-05-22 00:55:31.250502	2025-05-22 00:55:31.250502	\N
178	Waiting for Spring English Manga Volume 1-2 Paperback Anashin Kodansha Comics		9.00	Like New	\N	2025-05-22 00:55:31.250729	2025-05-22 00:55:31.250729	\N
179	Soul Eater Manga English Lot Vol. 1, 3 Atsushi Ohkubo Yen Press Soft Cover		9.00	Very Good	\N	2025-05-22 00:55:31.250947	2025-05-22 00:55:31.250947	\N
180	Bogle Volume 1 & 2 Shino Taira English Manga Go! Comi Graphic Novel OOP TPB		9.00	Good	\N	2025-05-22 00:55:31.251159	2025-05-22 00:55:31.251159	\N
181	Blade Of The Immortal Volumes 1-3 English Manga Dark Horse Comics Softcover		12.60	Good	\N	2025-05-22 00:55:31.251372	2025-05-22 00:55:31.251372	\N
182	Tsubaki Chou Lonely Planet Vol 1-2 English Manga by Mika Yamamori, Yen Press		9.90	Like New	\N	2025-05-22 00:55:31.251951	2025-05-22 00:55:31.251951	\N
183	High School DXD English Manga Vol 1&2 Hiroji Mishima Yen Press Softcover		11.70	Like New	\N	2025-05-22 00:55:31.252168	2025-05-22 00:55:31.252168	\N
184	Moriarty the Patriot, Vol. 1,  4, 5 English Manga Soft Cover		10.80	Very Good	\N	2025-05-22 00:55:31.25238	2025-05-22 00:55:31.25238	\N
185	Jujutsu Kaisen English Manga Vol. 0-1, 13-15 Gege Akutami Shonen Jump Viz Media		22.50	Like New	\N	2025-05-22 00:55:31.252594	2025-05-22 00:55:31.252594	\N
186	Gantz Volume 1, 6, 8, 11, 12, 17, 22, 34 - Manga English Lot Of 8 OOP Hiroya Oku		81.00	Like New	\N	2025-05-22 00:55:31.25281	2025-05-22 00:55:31.25281	\N
187	Gyo 1 + Uzumaki: Spiral Into Horror 3 Junji Ito English Manga Viz Paperback		27.00	Very Good	\N	2025-05-22 00:55:31.253027	2025-05-22 00:55:31.253027	\N
188	Planet Ladder Vol. 1, 2, 3, 4, 5 Yuri Narushima Tokyopop English Manga Books Lot		18.00	Very Good	\N	2025-05-22 00:55:31.253243	2025-05-22 00:55:31.253243	\N
189	MY HERO ACADEMIA Vol 1 - Vol 16 Set English Version Kohei Horikoshi Manga Comic		40.50	Like New	\N	2025-05-22 00:55:31.25346	2025-05-22 00:55:31.25346	\N
190	Demon Slayer Kimetsu No Yaiba Banpresto Figures Lot 4 New Anime Manga Vol.1		27.00	New	\N	2025-05-22 00:55:31.253682	2025-05-22 00:55:31.253682	\N
191	Lot of 14 Hunter X Hunter Vol #1-4, 6-10, 15, 16, 25-27 English Manga		90.00	Brand New	\N	2025-05-22 00:55:31.253926	2025-05-22 00:55:31.253926	\N
192	Initial D Tokyopop English Manga Volumes 2-7 Shuichi Shigeno OOP softcover		45.00	Good	\N	2025-05-22 00:55:31.254142	2025-05-22 00:55:31.254142	\N
193	From the Red Fog Vol 1-5 Complete English Manga Set - New Mosae Nohara Shonen		31.50	Brand New	\N	2025-05-22 00:55:31.25436	2025-05-22 00:55:31.25436	\N
194	Rooster Fighter Manga in English by Shu Sakuratani Vol 1-6 Graphic Novel New		45.00	Brand New	\N	2025-05-22 00:55:31.254572	2025-05-22 00:55:31.254572	\N
195	Toilet-Bound Hanako-Kun English Manga Lot Volumes 1-13 Aidairo		63.00	Like New	\N	2025-05-22 00:55:31.25481	2025-05-22 00:55:31.25481	\N
196	BEASTARS Vol. 1-2, 6-18 Manga Lot Set English (15 Volumes)  Viz Media Soft Cover		112.50	Like New	\N	2025-05-22 00:55:31.255088	2025-05-22 00:55:31.255088	\N
197	Twin Star Exorcists Manga Volumes 1-24 In English Graphic Novel Viz		162.00	Like New	\N	2025-05-22 00:55:31.255383	2025-05-22 00:55:31.255383	\N
198	Spy X Family Manga , Set Of 7 Volumes (Volumes 1-7) Manga Lot, By Tatsuya Endo		29.70	Brand New	\N	2025-05-22 00:55:31.25566	2025-05-22 00:55:31.25566	\N
199	Fire Force English Manga Volumes 1-14 Atsushi Ohkubo Soul Eater Kodansha		58.50	Like New	\N	2025-05-22 00:55:31.25593	2025-05-22 00:55:31.25593	\N
200	Soul Eater The Perfect Edition Manga Volume 1-3 Hardcover Copies		31.50	Like New	\N	2025-05-22 00:55:31.256152	2025-05-22 00:55:31.256152	\N
201	Hayate the Combat Butler by Kenjiro Hata Vols 3-7, 11, 13		27.00	Very Good	\N	2025-05-22 00:55:31.256379	2025-05-22 00:55:31.256379	\N
202	Shikimori's Not Just A Cutie 1-6 English Manga Keigo Maki Kodansha Anime Lot		45.00	Good	\N	2025-05-22 00:55:31.256602	2025-05-22 00:55:31.256602	\N
203	Goodbye my rose garden Manga Vol 1-3 English Graphic Novels Complete Seven Seas		31.50	Like New	\N	2025-05-22 00:55:31.256845	2025-05-22 00:55:31.256845	\N
204	Master Keaton ( Vol. 1-12) English Manga Complete Set Viz Media Naoki Urasawa		180.00	Like New	\N	2025-05-22 00:55:31.25706	2025-05-22 00:55:31.25706	\N
205	Slasher Maidens 1-3 English Manga Lot Yen Press Tetsuya Tashiro Softcover		22.50	Like New	\N	2025-05-22 00:55:31.257272	2025-05-22 00:55:31.257272	\N
206	Fushigi Yugi The Mysterious Play vol 1-18  (+ Extra) Complete Set English Manga		108.00	Very Good	\N	2025-05-22 00:55:31.257485	2025-05-22 00:55:31.257485	\N
207	Boarding School Juliet English Manga Volumes 1-14 US Authentic Softcover Kodansh		135.00	Like New	\N	2025-05-22 00:55:31.257698	2025-05-22 00:55:31.257698	\N
208	Inside Mari Manga Vol 2-7 Almost Complete English Denpa by Shuzo Oshimi		207.00	Like New	\N	2025-05-22 00:55:31.257915	2025-05-22 00:55:31.257915	\N
209	Aria the Masterpiece Vol 1-7 English Manga Complete Set Kozue Amano Tokyopop OOP		135.00	Brand New	\N	2025-05-22 00:55:31.258132	2025-05-22 00:55:31.258132	\N
210	Jujutsu Kaisen Volumes 0-17 (No 12) English Manga Shonen Jump Viz Media Gege		90.00	Brand New	\N	2025-05-22 00:55:31.258354	2025-05-22 00:55:31.258354	\N
\.


--
-- TOC entry 5103 (class 0 OID 16713)
-- Dependencies: 230
-- Data for Name: sale_volumes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sale_volumes (sale_volume_id, sale_id, volume_id) FROM stdin;
1	1	2
2	1	1
3	1	3
4	1	4
5	1	5
6	1	6
7	1	7
8	2	2
9	2	1
10	2	3
11	2	4
12	2	5
13	2	6
14	2	7
15	3	2
16	3	1
17	3	3
18	3	4
19	3	5
20	4	1
21	4	3
22	5	2
23	5	1
24	5	3
25	5	4
26	5	5
27	5	6
28	5	7
29	49	2
30	49	1
31	49	3
32	49	4
33	49	5
34	49	6
35	49	7
36	49	13
37	49	14
38	49	15
39	49	16
40	49	17
41	50	2
42	50	1
43	50	3
44	50	4
45	50	5
46	50	6
47	50	7
48	50	13
49	50	14
50	50	15
51	50	16
52	50	17
53	51	2
54	51	1
55	51	3
56	51	4
57	51	5
58	51	13
59	51	14
60	51	15
61	52	1
62	52	3
63	52	13
64	53	2
65	53	1
66	53	3
67	53	4
68	53	5
69	53	6
70	53	7
71	53	13
72	53	14
73	53	15
74	53	16
75	53	17
76	54	2
77	54	1
78	54	3
79	54	4
80	54	5
81	54	6
82	54	7
83	54	13
84	54	14
85	54	15
86	54	16
87	54	17
88	54	23
89	54	24
90	54	25
91	54	26
92	54	27
93	55	2
94	55	1
95	55	3
96	55	4
97	55	5
98	55	6
99	55	7
100	55	13
101	55	14
102	55	15
103	55	16
104	55	17
105	55	23
106	55	24
107	55	25
108	55	26
109	55	27
110	56	2
111	56	1
112	56	3
113	56	4
114	56	5
115	56	13
116	56	14
117	56	15
118	56	23
119	56	24
120	56	25
121	57	1
122	57	3
123	57	13
124	57	23
125	58	2
126	58	1
127	58	3
128	58	4
129	58	5
130	58	6
131	58	7
132	58	13
133	58	14
134	58	15
135	58	16
136	58	17
137	58	23
138	58	24
139	58	25
140	58	26
141	58	27
\.


--
-- TOC entry 5117 (class 0 OID 28383)
-- Dependencies: 244
-- Data for Name: sell_submission_notes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sell_submission_notes (id, submission_id, note, status, created_at) FROM stdin;
\.


--
-- TOC entry 5115 (class 0 OID 28371)
-- Dependencies: 242
-- Data for Name: sell_submissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sell_submissions (id, full_name, email, phone, num_items, overall_condition, item_details, photo_paths, submitted_at, status, note, status_updated_at) FROM stdin;
2	Alberto Adame	albertoadame28@yahoo.com	6097746011	20	Like New	[]	["uploads\\/sell-photos\\/sell_682ebc0251c035.08562419.jpg","uploads\\/sell-photos\\/sell_682ebc0251db63.62981650.jpg","uploads\\/sell-photos\\/sell_682ebc0251f181.68396792.jpg","uploads\\/sell-photos\\/sell_682ebc02520566.51966589.jpg"]	2025-05-22 01:54:10.336627	Incomplete		2025-05-22 02:18:48.527093
\.


--
-- TOC entry 5095 (class 0 OID 16390)
-- Dependencies: 222
-- Data for Name: series; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.series (series_id, name, publisher, total_volumes, status, created_at, updated_at) FROM stdin;
1	Naruto	VIZ Media	72	completed	2025-04-03 19:55:30.127-04	2025-04-03 19:55:30.127-04
4	One Piece	VIZ Media	110	ongoing	2025-04-07 22:23:46.475-04	2025-04-07 22:23:46.475-04
5	Demon Slayer	VIZ Media	23	completed	2025-04-07 22:23:46.517-04	2025-04-07 22:23:46.517-04
6	My Hero Academia	VIZ Media	40	ongoing	2025-04-07 22:23:46.521-04	2025-04-07 22:23:46.521-04
7	Death Note	VIZ Media	12	completed	2025-04-07 22:23:46.525-04	2025-04-07 22:23:46.525-04
8	Attack on Titan	Kodansha Comics	34	completed	2025-04-07 22:23:46.532-04	2025-04-07 22:23:46.532-04
\.


--
-- TOC entry 5097 (class 0 OID 16446)
-- Dependencies: 224
-- Data for Name: volumes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.volumes (volume_id, volume_number, isbn, retail_price, release_date, cover_image, created_at, updated_at, series_id) FROM stdin;
2	2	9781569319017	9.99	2003-12-13 19:00:00-05	\N	2025-04-03 20:39:14.351-04	2025-04-03 20:39:14.351-04	1
1	1	9781569319000	10.99	2003-08-08 20:00:00-04	\N	2025-04-03 20:38:55.195-04	2025-04-03 20:40:06.018-04	1
3	1	9781569319001	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
4	2	9781569319002	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
5	3	9781569319003	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
6	4	9781569319004	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
7	5	9781569319005	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
8	6	9781569319006	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
9	7	9781569319007	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
10	8	9781569319008	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
11	9	9781569319009	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
12	10	97815693190010	9.99	\N	\N	2025-04-03 21:09:46.421-04	2025-04-03 21:09:46.421-04	1
13	1	9781569319001	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
14	2	9781569319002	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
15	3	9781569319003	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
16	4	9781569319004	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
17	5	9781569319005	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
18	6	9781569319006	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
19	7	9781569319007	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
20	8	9781569319008	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
21	9	9781569319009	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
22	10	97815693190010	9.99	\N	\N	2025-04-07 22:27:23.649-04	2025-04-07 22:27:23.649-04	1
23	1	9781569319001	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
24	2	9781569319002	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
25	3	9781569319003	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
26	4	9781569319004	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
27	5	9781569319005	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
28	6	9781569319006	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
29	7	9781569319007	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
30	8	9781569319008	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
31	9	9781569319009	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
32	10	97815693190010	9.99	\N	\N	2025-04-07 22:29:00.341-04	2025-04-07 22:29:00.341-04	1
\.


--
-- TOC entry 5135 (class 0 OID 0)
-- Dependencies: 227
-- Name: completed_sales_sale_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.completed_sales_sale_id_seq', 58, true);


--
-- TOC entry 5136 (class 0 OID 0)
-- Dependencies: 231
-- Name: inventory_inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.inventory_inventory_id_seq', 1, true);


--
-- TOC entry 5137 (class 0 OID 0)
-- Dependencies: 235
-- Name: listing_inventory_listing_inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.listing_inventory_listing_inventory_id_seq', 1, true);


--
-- TOC entry 5138 (class 0 OID 0)
-- Dependencies: 233
-- Name: listings_listing_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.listings_listing_id_seq', 1, true);


--
-- TOC entry 5139 (class 0 OID 0)
-- Dependencies: 225
-- Name: price_history_price_history_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.price_history_price_history_id_seq', 4, true);


--
-- TOC entry 5140 (class 0 OID 0)
-- Dependencies: 239
-- Name: product_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.product_images_id_seq', 1, false);


--
-- TOC entry 5141 (class 0 OID 0)
-- Dependencies: 237
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.products_id_seq', 210, true);


--
-- TOC entry 5142 (class 0 OID 0)
-- Dependencies: 229
-- Name: sale_volumes_sale_volume_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sale_volumes_sale_volume_id_seq', 141, true);


--
-- TOC entry 5143 (class 0 OID 0)
-- Dependencies: 243
-- Name: sell_submission_notes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sell_submission_notes_id_seq', 7, true);


--
-- TOC entry 5144 (class 0 OID 0)
-- Dependencies: 241
-- Name: sell_submissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.sell_submissions_id_seq', 2, true);


--
-- TOC entry 5145 (class 0 OID 0)
-- Dependencies: 221
-- Name: series_series_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.series_series_id_seq', 8, true);


--
-- TOC entry 5146 (class 0 OID 0)
-- Dependencies: 223
-- Name: volumes_volume_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.volumes_volume_id_seq', 32, true);


--
-- TOC entry 4919 (class 2606 OID 16711)
-- Name: completed_sales completed_sales_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.completed_sales
    ADD CONSTRAINT completed_sales_pkey PRIMARY KEY (sale_id);


--
-- TOC entry 4923 (class 2606 OID 18581)
-- Name: inventory inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_pkey PRIMARY KEY (inventory_id);


--
-- TOC entry 4927 (class 2606 OID 18605)
-- Name: listing_inventory listing_inventory_listing_id_inventory_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listing_inventory
    ADD CONSTRAINT listing_inventory_listing_id_inventory_id_key UNIQUE (listing_id, inventory_id);


--
-- TOC entry 4929 (class 2606 OID 18603)
-- Name: listing_inventory listing_inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listing_inventory
    ADD CONSTRAINT listing_inventory_pkey PRIMARY KEY (listing_inventory_id);


--
-- TOC entry 4925 (class 2606 OID 18596)
-- Name: listings listings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listings
    ADD CONSTRAINT listings_pkey PRIMARY KEY (listing_id);


--
-- TOC entry 4917 (class 2606 OID 16693)
-- Name: price_history price_history_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.price_history
    ADD CONSTRAINT price_history_pkey PRIMARY KEY (price_history_id);


--
-- TOC entry 4935 (class 2606 OID 28359)
-- Name: product_images product_images_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_pkey PRIMARY KEY (id);


--
-- TOC entry 4932 (class 2606 OID 28350)
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- TOC entry 4921 (class 2606 OID 16718)
-- Name: sale_volumes sale_volumes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sale_volumes
    ADD CONSTRAINT sale_volumes_pkey PRIMARY KEY (sale_volume_id);


--
-- TOC entry 4939 (class 2606 OID 28391)
-- Name: sell_submission_notes sell_submission_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sell_submission_notes
    ADD CONSTRAINT sell_submission_notes_pkey PRIMARY KEY (id);


--
-- TOC entry 4937 (class 2606 OID 28379)
-- Name: sell_submissions sell_submissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sell_submissions
    ADD CONSTRAINT sell_submissions_pkey PRIMARY KEY (id);


--
-- TOC entry 4785 (class 2606 OID 23051)
-- Name: series series_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key UNIQUE (name);


--
-- TOC entry 4787 (class 2606 OID 23053)
-- Name: series series_name_key1; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key1 UNIQUE (name);


--
-- TOC entry 4789 (class 2606 OID 23067)
-- Name: series series_name_key10; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key10 UNIQUE (name);


--
-- TOC entry 4791 (class 2606 OID 23069)
-- Name: series series_name_key11; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key11 UNIQUE (name);


--
-- TOC entry 4793 (class 2606 OID 23071)
-- Name: series series_name_key12; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key12 UNIQUE (name);


--
-- TOC entry 4795 (class 2606 OID 23073)
-- Name: series series_name_key13; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key13 UNIQUE (name);


--
-- TOC entry 4797 (class 2606 OID 23075)
-- Name: series series_name_key14; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key14 UNIQUE (name);


--
-- TOC entry 4799 (class 2606 OID 23045)
-- Name: series series_name_key15; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key15 UNIQUE (name);


--
-- TOC entry 4801 (class 2606 OID 23077)
-- Name: series series_name_key16; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key16 UNIQUE (name);


--
-- TOC entry 4803 (class 2606 OID 23079)
-- Name: series series_name_key17; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key17 UNIQUE (name);


--
-- TOC entry 4805 (class 2606 OID 23043)
-- Name: series series_name_key18; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key18 UNIQUE (name);


--
-- TOC entry 4807 (class 2606 OID 23081)
-- Name: series series_name_key19; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key19 UNIQUE (name);


--
-- TOC entry 4809 (class 2606 OID 23049)
-- Name: series series_name_key2; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key2 UNIQUE (name);


--
-- TOC entry 4811 (class 2606 OID 23083)
-- Name: series series_name_key20; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key20 UNIQUE (name);


--
-- TOC entry 4813 (class 2606 OID 23041)
-- Name: series series_name_key21; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key21 UNIQUE (name);


--
-- TOC entry 4815 (class 2606 OID 23039)
-- Name: series series_name_key22; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key22 UNIQUE (name);


--
-- TOC entry 4817 (class 2606 OID 23085)
-- Name: series series_name_key23; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key23 UNIQUE (name);


--
-- TOC entry 4819 (class 2606 OID 23037)
-- Name: series series_name_key24; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key24 UNIQUE (name);


--
-- TOC entry 4821 (class 2606 OID 23035)
-- Name: series series_name_key25; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key25 UNIQUE (name);


--
-- TOC entry 4823 (class 2606 OID 23025)
-- Name: series series_name_key26; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key26 UNIQUE (name);


--
-- TOC entry 4825 (class 2606 OID 23027)
-- Name: series series_name_key27; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key27 UNIQUE (name);


--
-- TOC entry 4827 (class 2606 OID 23033)
-- Name: series series_name_key28; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key28 UNIQUE (name);


--
-- TOC entry 4829 (class 2606 OID 23029)
-- Name: series series_name_key29; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key29 UNIQUE (name);


--
-- TOC entry 4831 (class 2606 OID 23055)
-- Name: series series_name_key3; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key3 UNIQUE (name);


--
-- TOC entry 4833 (class 2606 OID 23031)
-- Name: series series_name_key30; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key30 UNIQUE (name);


--
-- TOC entry 4835 (class 2606 OID 23087)
-- Name: series series_name_key31; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key31 UNIQUE (name);


--
-- TOC entry 4837 (class 2606 OID 23089)
-- Name: series series_name_key32; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key32 UNIQUE (name);


--
-- TOC entry 4839 (class 2606 OID 23091)
-- Name: series series_name_key33; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key33 UNIQUE (name);


--
-- TOC entry 4841 (class 2606 OID 23093)
-- Name: series series_name_key34; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key34 UNIQUE (name);


--
-- TOC entry 4843 (class 2606 OID 23095)
-- Name: series series_name_key35; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key35 UNIQUE (name);


--
-- TOC entry 4845 (class 2606 OID 23097)
-- Name: series series_name_key36; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key36 UNIQUE (name);


--
-- TOC entry 4847 (class 2606 OID 23023)
-- Name: series series_name_key37; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key37 UNIQUE (name);


--
-- TOC entry 4849 (class 2606 OID 23099)
-- Name: series series_name_key38; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key38 UNIQUE (name);


--
-- TOC entry 4851 (class 2606 OID 23101)
-- Name: series series_name_key39; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key39 UNIQUE (name);


--
-- TOC entry 4853 (class 2606 OID 23047)
-- Name: series series_name_key4; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key4 UNIQUE (name);


--
-- TOC entry 4855 (class 2606 OID 23103)
-- Name: series series_name_key40; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key40 UNIQUE (name);


--
-- TOC entry 4857 (class 2606 OID 23021)
-- Name: series series_name_key41; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key41 UNIQUE (name);


--
-- TOC entry 4859 (class 2606 OID 23105)
-- Name: series series_name_key42; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key42 UNIQUE (name);


--
-- TOC entry 4861 (class 2606 OID 23019)
-- Name: series series_name_key43; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key43 UNIQUE (name);


--
-- TOC entry 4863 (class 2606 OID 23107)
-- Name: series series_name_key44; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key44 UNIQUE (name);


--
-- TOC entry 4865 (class 2606 OID 23109)
-- Name: series series_name_key45; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key45 UNIQUE (name);


--
-- TOC entry 4867 (class 2606 OID 23017)
-- Name: series series_name_key46; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key46 UNIQUE (name);


--
-- TOC entry 4869 (class 2606 OID 23111)
-- Name: series series_name_key47; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key47 UNIQUE (name);


--
-- TOC entry 4871 (class 2606 OID 23015)
-- Name: series series_name_key48; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key48 UNIQUE (name);


--
-- TOC entry 4873 (class 2606 OID 23113)
-- Name: series series_name_key49; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key49 UNIQUE (name);


--
-- TOC entry 4875 (class 2606 OID 23057)
-- Name: series series_name_key5; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key5 UNIQUE (name);


--
-- TOC entry 4877 (class 2606 OID 23115)
-- Name: series series_name_key50; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key50 UNIQUE (name);


--
-- TOC entry 4879 (class 2606 OID 23013)
-- Name: series series_name_key51; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key51 UNIQUE (name);


--
-- TOC entry 4881 (class 2606 OID 23117)
-- Name: series series_name_key52; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key52 UNIQUE (name);


--
-- TOC entry 4883 (class 2606 OID 23119)
-- Name: series series_name_key53; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key53 UNIQUE (name);


--
-- TOC entry 4885 (class 2606 OID 23011)
-- Name: series series_name_key54; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key54 UNIQUE (name);


--
-- TOC entry 4887 (class 2606 OID 23009)
-- Name: series series_name_key55; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key55 UNIQUE (name);


--
-- TOC entry 4889 (class 2606 OID 22997)
-- Name: series series_name_key56; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key56 UNIQUE (name);


--
-- TOC entry 4891 (class 2606 OID 23007)
-- Name: series series_name_key57; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key57 UNIQUE (name);


--
-- TOC entry 4893 (class 2606 OID 23005)
-- Name: series series_name_key58; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key58 UNIQUE (name);


--
-- TOC entry 4895 (class 2606 OID 22999)
-- Name: series series_name_key59; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key59 UNIQUE (name);


--
-- TOC entry 4897 (class 2606 OID 23059)
-- Name: series series_name_key6; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key6 UNIQUE (name);


--
-- TOC entry 4899 (class 2606 OID 23003)
-- Name: series series_name_key60; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key60 UNIQUE (name);


--
-- TOC entry 4901 (class 2606 OID 23001)
-- Name: series series_name_key61; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key61 UNIQUE (name);


--
-- TOC entry 4903 (class 2606 OID 22995)
-- Name: series series_name_key62; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key62 UNIQUE (name);


--
-- TOC entry 4905 (class 2606 OID 23121)
-- Name: series series_name_key63; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key63 UNIQUE (name);


--
-- TOC entry 4907 (class 2606 OID 23061)
-- Name: series series_name_key7; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key7 UNIQUE (name);


--
-- TOC entry 4909 (class 2606 OID 23063)
-- Name: series series_name_key8; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key8 UNIQUE (name);


--
-- TOC entry 4911 (class 2606 OID 23065)
-- Name: series series_name_key9; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_name_key9 UNIQUE (name);


--
-- TOC entry 4913 (class 2606 OID 16398)
-- Name: series series_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.series
    ADD CONSTRAINT series_pkey PRIMARY KEY (series_id);


--
-- TOC entry 4915 (class 2606 OID 16453)
-- Name: volumes volumes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.volumes
    ADD CONSTRAINT volumes_pkey PRIMARY KEY (volume_id);


--
-- TOC entry 4930 (class 1259 OID 28365)
-- Name: idx_ebay_item_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_ebay_item_id ON public.products USING btree (ebay_item_id);


--
-- TOC entry 4933 (class 1259 OID 28366)
-- Name: idx_product_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_product_id ON public.product_images USING btree (product_id);


--
-- TOC entry 4944 (class 2606 OID 22833)
-- Name: inventory inventory_volume_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_volume_id_fkey FOREIGN KEY (volume_id) REFERENCES public.volumes(volume_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4945 (class 2606 OID 22849)
-- Name: listing_inventory listing_inventory_inventory_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listing_inventory
    ADD CONSTRAINT listing_inventory_inventory_id_fkey FOREIGN KEY (inventory_id) REFERENCES public.inventory(inventory_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4946 (class 2606 OID 22844)
-- Name: listing_inventory listing_inventory_listing_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.listing_inventory
    ADD CONSTRAINT listing_inventory_listing_id_fkey FOREIGN KEY (listing_id) REFERENCES public.listings(listing_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4941 (class 2606 OID 22806)
-- Name: price_history price_history_series_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.price_history
    ADD CONSTRAINT price_history_series_id_fkey FOREIGN KEY (series_id) REFERENCES public.series(series_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4947 (class 2606 OID 28360)
-- Name: product_images product_images_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.product_images
    ADD CONSTRAINT product_images_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- TOC entry 4942 (class 2606 OID 22823)
-- Name: sale_volumes sale_volumes_sale_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sale_volumes
    ADD CONSTRAINT sale_volumes_sale_id_fkey FOREIGN KEY (sale_id) REFERENCES public.completed_sales(sale_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4943 (class 2606 OID 22828)
-- Name: sale_volumes sale_volumes_volume_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sale_volumes
    ADD CONSTRAINT sale_volumes_volume_id_fkey FOREIGN KEY (volume_id) REFERENCES public.volumes(volume_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- TOC entry 4948 (class 2606 OID 28392)
-- Name: sell_submission_notes sell_submission_notes_submission_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sell_submission_notes
    ADD CONSTRAINT sell_submission_notes_submission_id_fkey FOREIGN KEY (submission_id) REFERENCES public.sell_submissions(id) ON DELETE CASCADE;


--
-- TOC entry 4940 (class 2606 OID 23124)
-- Name: volumes volumes_series_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.volumes
    ADD CONSTRAINT volumes_series_id_fkey FOREIGN KEY (series_id) REFERENCES public.series(series_id) ON UPDATE CASCADE ON DELETE SET NULL;


-- Completed on 2025-05-22 02:49:34

--
-- PostgreSQL database dump complete
--

