#!/usr/bin/env python
# -*- coding: utf-8 -*-
from lxml import html
from json import dump,loads
from requests import get
import json
from re import sub
from dateutil import parser as dateparser
from time import sleep
import math
import sys
import psycopg2

def ParseReviews(asin, amazon_url):
    headers = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36'}
    connection = psycopg2.connect(dbname='bender', user='bender', password='bender', host='localhost')
    cursor = connection.cursor()
    for i in range(5):
        response = get(amazon_url, headers = headers, verify=False, timeout=30)
        if response.status_code == 404:
            return {"url": amazon_url, "error": "page not found"}
        if response.status_code != 200:
            continue
        
        # Removing the null bytes from the response.
        cleaned_response = response.text.replace('\x00', '')
        
        parser = html.fromstring(cleaned_response)
        XPATH_AGGREGATE = '//span[@id="acrCustomerReviewText"]'
        XPATH_REVIEW_SECTION_1 = '//div[contains(@id,"reviews-summary")]'
        XPATH_REVIEW_SECTION_2 = '//div[@data-hook="review"]'
        XPATH_AGGREGATE_RATING = '//table[@id="histogramTable"]//tr'
        XPATH_PRODUCT_NAME = '//h1//a[@data-hook="product-link"]//text()'
        XPATH_PRODUCT_PRICE = '//span[@class="a-color-price arp-price"]/text()'

        raw_product_price = parser.xpath(XPATH_PRODUCT_PRICE)
        raw_product_name = parser.xpath(XPATH_PRODUCT_NAME)
        total_ratings  = parser.xpath(XPATH_AGGREGATE_RATING)
        reviews = parser.xpath(XPATH_REVIEW_SECTION_1)

        product_price = ''.join(raw_product_price).replace(',', '')
        product_name = ''.join(raw_product_name).strip()

        if not reviews:
            reviews = parser.xpath(XPATH_REVIEW_SECTION_2)
        ratings_dict = {}
        reviews_list = []

        # Grabing the rating  section in product page
        for ratings in total_ratings:
            extracted_rating = ratings.xpath('./td//a//text()')
            if extracted_rating:
                rating_key = extracted_rating[0] 
                raw_raing_value = extracted_rating[1]
                rating_value = raw_raing_value
                if rating_key:
                    ratings_dict.update({rating_key: rating_value})
        
        # Parsing individual reviews
        for review in reviews:
            XPATH_RATING  = './/i[@data-hook="review-star-rating"]//text()'
            XPATH_REVIEW_HEADER = './/a[@data-hook="review-title"]//text()'
            XPATH_REVIEW_POSTED_DATE = './/span[@data-hook="review-date"]//text()'
            XPATH_REVIEW_TEXT_1 = './/span[@data-hook="review-body"]//text()'
            XPATH_REVIEW_TEXT_2 = './/div[@data-hook="review-collapsed"]//text()'
            XPATH_REVIEW_COMMENTS = './/span[@data-hook="helpful-vote-statement"]//text()'
            XPATH_AUTHOR = './/span[contains(@class,"profile-name")]//text()'
            XPATH_REVIEW_TEXT_3 = './/div[contains(@id,"dpReviews")]/div/text()'
            
            raw_review_author = review.xpath(XPATH_AUTHOR)
            raw_review_rating = review.xpath(XPATH_RATING)
            raw_review_header = review.xpath(XPATH_REVIEW_HEADER)
            raw_review_posted_date = review.xpath(XPATH_REVIEW_POSTED_DATE)
            raw_review_text1 = review.xpath(XPATH_REVIEW_TEXT_1)
            raw_review_text2 = review.xpath(XPATH_REVIEW_TEXT_2)
            raw_review_text3 = review.xpath(XPATH_REVIEW_TEXT_3)

            # Cleaning data
            author = ' '.join(' '.join(raw_review_author).split())
            review_rating = ''.join(raw_review_rating).replace('out of 5 stars', '')
            review_header = ' '.join(' '.join(raw_review_header).split())

            try:
                review_posted_date = dateparser.parse(''.join(raw_review_posted_date)).strftime('%d %b %Y')
            except:
                review_posted_date = None
            review_text = ' '.join(' '.join(raw_review_text1).split())

            # Grabbing hidden comments if present
            if raw_review_text2:
                json_loaded_review_data = loads(raw_review_text2[0])
                json_loaded_review_data_text = json_loaded_review_data['rest']
                cleaned_json_loaded_review_data_text = re.sub('<.*?>', '', json_loaded_review_data_text)
                full_review_text = review_text+cleaned_json_loaded_review_data_text
            else:
                full_review_text = review_text
            if not raw_review_text1:
                full_review_text = ' '.join(' '.join(raw_review_text3).split())

            raw_review_comments = review.xpath(XPATH_REVIEW_COMMENTS)
            review_comments = ''.join(raw_review_comments)
            review_comments = sub('[A-Za-z]', '', review_comments).strip()
            review_dict = {
                                'review_comment_count': review_comments,
                                'review_text': full_review_text,
                                'review_posted_date': review_posted_date,
                                'review_header': review_header,
                                'review_rating': review_rating,
                                'review_author': author

                            }
            cursor.execute("SELECT exists(SELECT * FROM reviews WHERE review_text = %s)", (full_review_text,))
            print(cursor)
            if not cursor.fetchone()[0]:
                site = 1
                query = "INSERT INTO reviews (id_site, is_translated, review_posted_date, review_header, review_text, review_rating, review_author, id_order, review_comment_count) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)"
                #проверка сайта не нужна, потому что этот скрипт рассчитан конкретно на амазон, надо еще будет подумать о разделении скриптов
                data = (site, False, review_posted_date, review_header, full_review_text, review_rating, author, 0, review_comments)
                cursor.execute(query, data)
            reviews_list.append(review_dict)

        data = {
                    'ratings': ratings_dict,
                    'reviews': reviews_list,
                    'url': amazon_url,
                    'name': product_name,
                    'price': product_price
                
                }
        connection.commit()
        cursor.close()
        connection.close()
        return data

    return {"error": "failed to process the page", "url": amazon_url}
            
def ParsePageCount(amazon_url):
    headers = {'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36'}
    for i in range(5):
        response = get(amazon_url, headers = headers, verify=False, timeout=30)
        if response.status_code == 404:
            return {"url": amazon_url, "error": "page not found"}
        if response.status_code != 200:
            continue

        cleaned_response = response.text.replace('\x00', '')
        parser = html.fromstring(cleaned_response)
        XPATH_REVIEWS_COUNT = './/span[@data-hook="total-review-count"]//text()'
        raw_review_count = parser.xpath(XPATH_REVIEWS_COUNT)
        page_count = math.ceil(float(raw_review_count[0].replace(',', '')) / 10)
    return page_count

def ReadAsin(asin):
    # Add your own ASINs here
    extracted_data = []
    page_number = 1
    amazon_url = "http://www.amazon.com/product-reviews/" + asin
    total_page_count = ParsePageCount(amazon_url) + 1
    while page_number < total_page_count:
            try:
                if page_number == 1:
                    print("Downloading and processing page http://www.amazon.com/product-reviews/" + asin)
                    extracted_data.append(ParseReviews(asin, amazon_url))
                else:
                    amazon_url = "http://www.amazon.com/product-reviews/" + asin + "/ref=cm_cr_getr_d_paging_btm_next_" + str(page_number) + "?ie=UTF8&reviewerType=all_reviews&pageNumber=" + str(page_number)
                    print("Next page http://www.amazon.com/product-reviews/" + asin + "/ref=cm_cr_getr_d_paging_btm_next_" + str(page_number) + "?ie=UTF8&reviewerType=all_reviews&pageNumber=" + str(page_number))
                    extracted_data.append(ParseReviews(asin, amazon_url))
                page_number += 1
                sleep(5)
            except Exception:
                break
    f = open('data.json', 'w')
    dump(extracted_data, f, indent=4)
    f.close()

if __name__ == "__main__":
    ReadAsin(sys.argv[1])
